#include <stdio.h>
#include <stdlib.h>

#include <math.h>
#include <time.h>

#include "globals.h"
#include "gifencoder.h"
#include "lzwCompressor.h"

#define GIF_HEADER "GIF89a"

//Weights for finding closest color in table
#define WEIGHT_H 0.6
#define WEIGHT_S 0.2
#define WEIGHT_V 0.2

//7 max -> means 2^(res + 1) as tablesize
#define colorResolution 7
#define pixelAspectRatio 0

#define allocateAtOnce 100

//informations from http://www.matthewflickinger.com/lab/whatsinagif/bits_and_bytes.asp

//private functions definitions
void littleEndianDump(char* writeInto, int* writeSize, int32_t data, int8_t bytes);
void bigEndianDump(char* writeInto, int* writeSize, int32_t data, int8_t bytes);
void gifencoder_createColorTable(GIF_STRUCTURE* gif);
void bubble_sort(int32_t* sortBy, int32_t* other, int array_size);
int find_closest_matching(GIF_STRUCTURE* gif, int32_t color);
void to_HSV(int32_t asRGB, float* h, float* s, float* v);
void gifencoder_writeColorTable(FILE* targetFile, GIF_STRUCTURE* gif);
void gifencoder_writeLoopExtension(FILE* targetFile, GIF_STRUCTURE* gif);
void gifencoder_writeImage(char** target, int* targetSize, int* maxTargetSize, int globColorTableSizeBit, GIF_STRUCTURE* gif, int number);
void gifencoder_writeDelayHeader(char** target, int* targetSize, int* maxTargetSize, GIF_STRUCTURE* gif, int transparentIdx, int isFirst);
void getChangedReagion(int32_t* imageData, int32_t* prevImageData, int width, int height, int* xs, int* xe, int* ys, int* ye);
void gifencoder_lzwCompressImage(char** target, int* targetSize, int* maxTargetSize, GIF_STRUCTURE* gif, int32_t* imageData,
    int32_t* prevImageData, int transparentIdx, int globColorTableSizeBit, int xs, int xe, int ys, int ye);


//public functions
/**
 * Initializes a gif structure that can be filled afterwards
 */
GIF_STRUCTURE* gifencoder_create(unsigned width, unsigned height, unsigned delay, LodePNGColorType colType) {
    GIF_STRUCTURE* retval;
    retval = (GIF_STRUCTURE*) calloc(1, sizeof(GIF_STRUCTURE));

    retval->height = height;
    retval->width = width;
    retval->images = 0;
    retval->numImages = 0;
    retval->maxImages = 0;
    retval->delay = delay;
    retval->colType = colType;

    retval->colors = calloc(allocateAtOnce, sizeof(int32_t));
    retval->colorAmount = calloc(allocateAtOnce, sizeof(int32_t));
    retval->maxColorsSize = allocateAtOnce;
    retval->colorsSize = 0;

    return retval;
}

/**
 * Adds a given imageData to the GIF (just add will not perform any calculations)
 */
void gifencoder_addImage(GIF_STRUCTURE* gif, unsigned char* imageData) {
    if(! gif->images) {
        gif->images = calloc(allocateAtOnce, sizeof(int32_t*));
        gif->maxImages = allocateAtOnce;
        gif->numImages = 0;
    } else if(gif->numImages == gif->maxImages) {
        //full -> realloc
        gif->maxImages += allocateAtOnce;
        gif->images = realloc(gif->images, sizeof(int32_t*) * (gif->maxImages));
        for(int inner = gif->maxImages - allocateAtOnce; inner < gif->maxImages; inner++) {
            gif->images[inner] = 0;
        }
    }

    int found;
    int32_t color;


    int32_t* newImageData = calloc(gif->width * gif->height, sizeof(int32_t));
    int r, g, b;
    for(int i = 0; i < gif->width * gif->height; i++) {
        if(gif->colType == LCT_RGBA) {
            r = imageData[i * 4];
            g = imageData[i * 4 + 1];
            b = imageData[i * 4 + 2];
        } else {
            printf("Unknown color type %u\n", gif->colType);
            exit(1);
        }

        color = (r << 16) + (g << 8) + b;
        newImageData[i] = color;

        found = 0;
        for(int j = 0; j < gif->colorsSize; j++) {
            if(gif->colors[j] == color) {
                found = 1;
                gif->colorAmount[j]++;
                break;
            }
        }
        if(! found) {
            if(gif->colorsSize == gif->maxColorsSize) {
                gif->maxColorsSize += allocateAtOnce;
                gif->colors = realloc(gif->colors, sizeof(int32_t) * gif->maxColorsSize);
                gif->colorAmount = realloc(gif->colorAmount, sizeof(int32_t) * gif->maxColorsSize);
                for(int inner = gif->maxColorsSize - allocateAtOnce; inner < gif->maxColorsSize; inner++) {
                    gif->colors[inner] = 0;
                    gif->colorAmount[inner] = 0;
                }
            }

            gif->colors[gif->colorsSize] = color;
            gif->colorAmount[gif->colorsSize] = 1;
            gif->colorsSize++;
        }
    }

    gif->images[gif->numImages] = newImageData;
    gif->numImages++;
}

/**
 * Creates and writes the gif
 * uses the data found in the GIF_STRUCTURE
 * @param targetFilePath the file we want to write into
 */
void gifencoder_encode(GIF_STRUCTURE* gif, char* targetFilePath) {
    #ifdef TIMING_DEBUG
    clock_t start = clock();
    #endif // TIMING_DEBUG
    FILE* targetFile = fopen(targetFilePath, "w");
    if(targetFile == NULL) {
        printf("unable to open output file");
        exit(1);
    }

    char* temp = calloc(100, sizeof(char));
    int tempSize = 0;

    fprintf(targetFile, GIF_HEADER);
    littleEndianDump(temp, &tempSize, gif->width, 2);
    littleEndianDump(temp, &tempSize, gif->height, 2);

    #ifdef TIMING_DEBUG
    clock_t preCol = clock();
    #endif // TIMING_DEBUG
    gifencoder_createColorTable(gif);
    #ifdef TIMING_DEBUG
    clock_t postCol = clock();
    printf("Timing precol: %ld u sec / globalColTbl: %ld u sec\n", (preCol - start), (postCol - preCol));
    #endif // TIMING_DEBUG

    int globColTblSizeBit = (int) ceil(log2f(gif->globalColorTableSize) - 1);
    gif->GIFglobalColorTableSize = 2 << globColTblSizeBit;


    //don't ask
    //global color table; max color Resolution; Thing is sorted
    temp[tempSize++] = 0b10001000 + globColTblSizeBit + (colorResolution << 4);
    //background color index
    temp[tempSize++] = 0;
    //pixel aspect ratio
    temp[tempSize++] = pixelAspectRatio;
    fwrite(temp, tempSize, 1, targetFile);

    gifencoder_writeColorTable(targetFile, gif);
    gifencoder_writeLoopExtension(targetFile, gif);

    char* encodedImage = malloc(1000 * sizeof(char));
    int encodedImageSize = 0;
    int maxEncodedImageSize = 1000;
    for(int i = 0; i < gif->numImages; i++) {
        #ifdef TIMING_DEBUG
        clock_t imageStart = clock();
        #endif // TIMING_DEBUG
        gifencoder_writeImage(&encodedImage, &encodedImageSize, &maxEncodedImageSize, globColTblSizeBit, gif, i);
        #ifdef TIMING_DEBUG
        clock_t imageEnd = clock();
        #endif // TIMING_DEBUG

        fwrite(encodedImage, encodedImageSize, 1, targetFile);
        encodedImageSize = 0;
        #ifdef TIMING_DEBUG
        clock_t imageWrite = clock();
        printf("Writing Image: prepare %ld u sec - write %ld u sec - ges %ld u sec\n", (imageEnd - imageStart), (imageWrite - imageEnd), (imageWrite - imageStart));
        #endif // TIMING_DEBUG
    }

    fprintf(targetFile, "%c", 0x3B);

    fclose(targetFile);
    targetFile = 0;
    free(encodedImage);
    free(temp);
    temp = 0;
}

void gifencoder_free(GIF_STRUCTURE* gif) {
    for(int i = 0; i < gif->numImages; i++) {
        if(gif->images[i]) {
            free(gif->images[i]);
//            gif->images[i] = 0;
        }
    }
    free(gif->images);
//    gif->images = 0;

//    gif->numImages = 0;
//    gif->maxImages = 0;

    free(gif->pictureColorMapKeys);
    free(gif->pictureColorMapValues);
//    gif->pictureColorMapKeys = 0;
//    gif->pictureColorMapValues = 0;
//    gif->pictureColorMapSize = 0;

    free(gif->globalColorTable);
    free(gif->globalHSVColorTableH);
    free(gif->globalHSVColorTableS);
    free(gif->globalHSVColorTableV);
//    gif->globalColorTable = 0;
//    gif->globalHSVColorTableH = 0;
//    gif->globalHSVColorTableS = 0;
//    gif->globalHSVColorTableV = 0;
    free(gif);
}


//private function code
void gifencoder_createColorTable(GIF_STRUCTURE* gif) {
    #ifdef TIMING_DEBUG
    clock_t start = clock();
    #endif // TIMING_DEBUG

    int32_t* colors = gif->colors;
    int32_t* colorAmount = gif->colorAmount;
    int colorsSize = gif->colorsSize;

    //Twice because bubble sort can fail if it needs to swap the first element
    //did not bother to test this for that implementation of bubble sort
    bubble_sort(colorAmount, colors, colorsSize);
    bubble_sort(colorAmount, colors, colorsSize);


    #ifdef TIMING_DEBUG
    clock_t sort = clock();
    #endif // TIMING_DEBUG

    //write into gif element
    gif->pictureColorMapKeys = calloc(colorsSize + 1, sizeof(int32_t));
    gif->pictureColorMapValues = calloc(colorsSize + 1, sizeof(int));
    gif->pictureColorMapSize = 0;

    int globalColorTableSize = 255;
    if(colorsSize < globalColorTableSize) {
        globalColorTableSize = colorsSize;
    }
    //+1 for transparent
    globalColorTableSize++;

    gif->globalColorTable = calloc(globalColorTableSize, sizeof(int32_t));
    gif->globalHSVColorTableH = calloc(globalColorTableSize, sizeof(float));
    gif->globalHSVColorTableS = calloc(globalColorTableSize, sizeof(float));
    gif->globalHSVColorTableV = calloc(globalColorTableSize, sizeof(float));
    gif->globalColorTableSize = globalColorTableSize;

    int i = 0;
    float h, s, v;
    for(; i < globalColorTableSize; i++) {
        gif->globalColorTable[i] = colors[i];
        to_HSV(colors[i], &h, &s, &v);
        gif->globalHSVColorTableH[i] = h;
        gif->globalHSVColorTableS[i] = s;
        gif->globalHSVColorTableV[i] = v;

        gif->pictureColorMapKeys[i] = colors[i];
        gif->pictureColorMapValues[i] = i;
        gif->pictureColorMapSize++;
    }

    for(; i < colorsSize; i++) {
        gif->pictureColorMapKeys[i] = colors[i];
        gif->pictureColorMapValues[i] = find_closest_matching(gif, colors[i]);
        gif->pictureColorMapSize++;
    }

//    for(int i = 0; i < colorsSize; i++) {
//        printf("Color: %d / Num: %d / NumTbl: %d\n", colors[i], colorAmount[i], gif->pictureColorMapValues[i]);
//    }
    free(colors);
    free(colorAmount);

    #ifdef TIMING_DEBUG
    clock_t end = clock();

    printf("Timing color: sort: %ld u sec / other: %ld u sec\n", (sort - start), (end - images));
    #endif // TIMING_DEBUG
}

void gifencoder_writeColorTable(FILE* targetFile, GIF_STRUCTURE* gif) {
    char* temp = calloc(gif->GIFglobalColorTableSize * 3 + 2, sizeof(char));
    int tempSize = 0;

    for(int i = 0; i < gif->GIFglobalColorTableSize; i++) {
        if(i < gif->globalColorTableSize) {
            bigEndianDump(temp, &tempSize, gif->globalColorTable[i], 3);
        } else {
            //fill with 0
            bigEndianDump(temp, &tempSize, 0, 3);
        }
    }
    fwrite(temp, tempSize, 1, targetFile);
    free(temp);
}

void gifencoder_writeLoopExtension(FILE* targetFile, GIF_STRUCTURE* gif) {
    char* temp = calloc(25, sizeof(char));
    int tempSize = 0;

    temp[tempSize++] = 0x21;
    temp[tempSize++] = 0xFF;
    temp[tempSize++] = 0x0B; // lenght of NETSCAPE2.0

    temp[tempSize++] = 'N'; //N
    temp[tempSize++] = 'E'; //E
    temp[tempSize++] = 'T'; //T
    temp[tempSize++] = 'S'; //S
    temp[tempSize++] = 'C'; //C
    temp[tempSize++] = 'A'; //A
    temp[tempSize++] = 'P'; //P
    temp[tempSize++] = 'E'; //E
    temp[tempSize++] = '2'; //2
    temp[tempSize++] = '.'; //.
    temp[tempSize++] = '0'; //0

    temp[tempSize++] = 0x03;
    temp[tempSize++] = 0x01;
    littleEndianDump(temp, &tempSize, 0, 2); //amount of looping 0 = unlimited
    temp[tempSize++] = 0x00;

    fwrite(temp, tempSize, 1, targetFile);
    free(temp);
}

void gifencoder_writeImage(char** target, int* targetSize, int* maxTargetSize, int globColorTableSizeBit, GIF_STRUCTURE* gif, int number) {
    int32_t* imageData = gif->images[number];
    int32_t* prevImageData = 0;

    if(number > 0) {
        prevImageData = gif->images[number - 1];
    }

    //last one is transparency
    int transparentIdx = gif->globalColorTableSize - 1;

    gifencoder_writeDelayHeader(target, targetSize, maxTargetSize, gif, transparentIdx, number == 0);
    (*target)[(*targetSize)++] = 0x2C;

    int xs, xe, ys, ye;
    if(! prevImageData) {
        littleEndianDump((*target), targetSize, 0, 2); //left
        littleEndianDump((*target), targetSize, 0, 2); //top
        littleEndianDump((*target), targetSize, gif->width, 2); //width
        littleEndianDump((*target), targetSize, gif->height, 2); //height
        xs = 0; ys = 0; xe = gif->height - 1; ye = gif->width - 1;
    } else {
        getChangedReagion(imageData, prevImageData, gif->width, gif->height, &xs, &xe, &ys, &ye);
        littleEndianDump((*target), targetSize, ys, 2); //left
        littleEndianDump((*target), targetSize, xs, 2); //top
        littleEndianDump((*target), targetSize, ye - ys + 1, 2); //width
        littleEndianDump((*target), targetSize, xe - xs + 1, 2); //height
    }

    (*target)[(*targetSize)++] = 0x00;
    gifencoder_lzwCompressImage(target, targetSize, maxTargetSize, gif, imageData, prevImageData, transparentIdx, globColorTableSizeBit, xs, xe, ys, ye);
}

void gifencoder_writeDelayHeader(char** target, int* targetSize, int* maxTargetSize, GIF_STRUCTURE* gif, int transparentIdx, int isFirst) {
    (*target)[(*targetSize)++] = 0x21;
    (*target)[(*targetSize)++] = 0xF9;
    (*target)[(*targetSize)++] = 0x04;

    if(isFirst) {
        (*target)[(*targetSize)++] = 0x06; //draw on bg / wait for user
    } else {
        (*target)[(*targetSize)++] = 0x05; //use transparency / show on top
    }

    littleEndianDump((*target), targetSize, gif->delay, 2);
    (*target)[(*targetSize)++] = transparentIdx & 0xFF;
    (*target)[(*targetSize)++] = 0x00;
}

void getChangedReagion(int32_t* imageData, int32_t* prevImageData, int width, int height, int* xs, int* xe, int* ys, int* ye) {
    *xs = 0;
    *ys = 0;
    *xe = height - 1;
    *ye = width - 1;

    //xs
    for(int i = *xs; i <= *xe; i++) {
        for(int j = *ys; j <= *ye; j++) {
            if(imageData[i * width + j] != prevImageData[i * width + j]) {
                *xs = i;
                break;
            }
        }
        if(*xs > 0) break;
    }

    //ys
    for(int j = *ys; j <= *ye; j++) {
        for(int i = *xs; i <= *xe; i++) {
            if(imageData[i * width + j] != prevImageData[i * width + j]) {
                *ys = j;
                break;
            }
        }
        if(*ys > 0) break;
    }

    //xe
    for(int i = *xe; i > *xs; i--) {
        for(int j = *ys; j <= *ye; j++) {
            if(imageData[i * width + j] != prevImageData[i * width + j]) {
                *xe = i;
                break;
            }
        }
        if(*xe < height - 1) break;
    }

    //ye
    for(int j = *ye; j > *ys; j--) {
        for(int i = *xs; i <= *xe; i++) {
            if(imageData[i * width + j] != prevImageData[i * width + j]) {
                *ye = j;
                break;
            }
        }
        if(*ye < width - 1) break;
    }
}

void gifencoder_lzwCompressImage(char** target, int* targetSize, int* maxTargetSize, GIF_STRUCTURE* gif, int32_t* imageData,
    int32_t* prevImageData, int transparentIdx, int globColorTableSizeBit, int xs, int xe, int ys, int ye) {
    LZW_STRUCTURE* compressor = lzwCompressor_create(globColorTableSizeBit);

    if(prevImageData) {
        (*target)[(*targetSize)++] = globColorTableSizeBit + 1;
        for(int i = xs; i <= xe; i++) {
            for(int j = ys; j <= ye; j++) {
                if(imageData[i * gif->width + j] != prevImageData[i * gif->width + j]) {
                    int32_t color = imageData[i * gif->width + j];
                    int colorID = -1;
                    for(int z = 0; z < gif->pictureColorMapSize; z++) {
                        if(gif->pictureColorMapKeys[z] == color) {
                            colorID = z;
                            break;
                        }
                    }
                    if(colorID == -1) {
                        printf("Unable to find color!!! %u", color);
                        exit(0);
                    }
                    lzwCompressor_append(compressor, gif->pictureColorMapValues[colorID]);
                } else {
                    lzwCompressor_append(compressor, transparentIdx);
                }
            }
        }
    } else {
        (*target)[(*targetSize)++] = globColorTableSizeBit + 1;
        for(int i = xs; i <= xe; i++) {
            for(int j = ys; j <= ye; j++) {
                int32_t color = imageData[i * gif->width + j];
                int colorID = -1;
                for(int z = 0; z < gif->pictureColorMapSize; z++) {
                    if(gif->pictureColorMapKeys[z] == color) {
                        colorID = z;
                        break;
                    }
                }
                if(colorID == -1) {
                    printf("Unable to find color!!! %u", color);
                    exit(0);
                }
                lzwCompressor_append(compressor, gif->pictureColorMapValues[colorID]);
            }
        }
    }
    lzwCompressor_finish(compressor);

    char* data = 0;
    int dataSize = 0;
    lzwCompressor_getCompressed(compressor, &data, &dataSize);

    int curPos = 0;
    while(dataSize - curPos > 255) {
        if(*maxTargetSize < *targetSize + 256) {
            *maxTargetSize += 1000;
            *target = realloc(*target, *maxTargetSize * sizeof(char));
        }

        (*target)[(*targetSize)++] = 0xFF;
        memcpy((*target) + *targetSize, data + curPos, 255);
        *targetSize += 255;
        curPos += 255;
    }
    if(*maxTargetSize < *targetSize + 256) {
        *maxTargetSize += 1000;
        *target = realloc(*target, *maxTargetSize * sizeof(char));
    }

    (*target)[(*targetSize)++] = (dataSize - curPos) & 0xFF;
    memcpy((*target) + *targetSize, data + curPos, (dataSize - curPos) & 0xFF);
    *targetSize += (dataSize - curPos) & 0xFF;

    (*target)[(*targetSize)++] = 0x00;

    lzwCompressor_free(compressor);
}

void bubble_sort(int32_t* sortBy, int32_t* other, int array_size) {
    int i, j;
    int32_t temp;

    for (i = 0; i < (array_size - 1); ++i)
    {
        for (j = 0; j < array_size - 1 - i; ++j )
        {
            if (sortBy[j] < sortBy[j+1])
            {
                temp = sortBy[j+1];
                sortBy[j+1] = sortBy[j];
                sortBy[j] = temp;

                temp = other[j+1];
                other[j+1] = other[j];
                other[j] = temp;
            }
        }
    }
}

int find_closest_matching(GIF_STRUCTURE* gif, int32_t color) {
    float h, s,v;
    to_HSV(color, &h, &s, &v);

    float minDiff = 10, diff;
    int closest = 0;

    for(int i = 0; i < gif->globalColorTableSize; i++) {
        diff = fabsf( gif->globalHSVColorTableH[i] - h ) * WEIGHT_H;
        diff += fabsf( gif->globalHSVColorTableS[i] - s ) * WEIGHT_S;
        diff += fabsf( gif->globalHSVColorTableV[i] - v ) * WEIGHT_V;

        if(diff < minDiff) {
            minDiff = diff;
            closest = i;
        }
    }

    return closest;
}

/**
 * rgb to HSV
 * HSV output from 0 to 1
 */
void to_HSV(int32_t asRGB, float* h, float* s, float* v) {
    float rFloat = ((float) ((asRGB >> 16) & 0xFF)) / 255;
    float gFloat = ((float) ((asRGB >> 8) & 0xFF)) / 255;
    float bFloat = ((float) (asRGB & 0xFF)) / 255;

    float maxFloat = rFloat;
    if(gFloat > maxFloat) maxFloat = gFloat;
    if(bFloat > maxFloat) maxFloat = bFloat;

    float minFloat = rFloat;
    if(gFloat < minFloat) minFloat = gFloat;
    if(bFloat < minFloat) minFloat = bFloat;
    float maxDiff = maxFloat - minFloat;

    *v = maxFloat;
    if(maxDiff == 0) {
        *h = 0;
        *s = 0;
    } else {
        *s = maxDiff / maxFloat;

        float diffR = (((maxFloat - rFloat) / 6) + (maxDiff / 2)) / maxDiff;
        float diffG = (((maxFloat - gFloat) / 6) + (maxDiff / 2)) / maxDiff;
        float diffB = (((maxFloat - bFloat) / 6) + (maxDiff / 2)) / maxDiff;

        if      (rFloat == maxFloat) *h = diffB - diffG;
        else if (gFloat == maxFloat) *h = (1/3) + diffR - diffB;
        else if (bFloat == maxFloat) *h = (2/3) + diffG - diffR;

        if(*h < 0) *h = *h + 1;
        if(*h > 1) *h = *h - 1;
    }
}

void littleEndianDump(char* writeInto, int* writeSize, int32_t data, int8_t bytes) {
    for(int i = *writeSize; i < bytes + *writeSize; i++) {
        writeInto[i] = data & 0xFF;
        data = data >> 8;
    }
    *writeSize += bytes;
}

void bigEndianDump(char* writeInto, int* writeSize, int32_t data, int8_t bytes) {
    for(int i = (*writeSize) + bytes - 1; i >= (*writeSize); i--) {
        writeInto[i] = data & 0xFF;
        data = data >> 8;
    }
    *writeSize += bytes;
}
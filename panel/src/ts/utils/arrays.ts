export function arrayEquals(array1: any[], array2: any[]) {
    return array1.length === array2.length && array1.every((val, i) => val === array2[i]);
}

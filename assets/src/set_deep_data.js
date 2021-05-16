/**
 * Allows setting a deep propertyPath (e.g. post.user.username)
 * into the given data, without mutating the original data.
 *
 * @param {Object} data
 * @param {string} propertyPath
 * @param {any} value
 * @return {Object}
 */
export function setDeepData(data, propertyPath, value) {
    // cheap way to deep clone simple data
    const finalData = JSON.parse(JSON.stringify(data));

    let currentLevelData = finalData;
    const parts = propertyPath.split('.');

    // change currentLevelData to the final depth object
    for (let i = 0; i < parts.length - 1; i++) {
        currentLevelData = currentLevelData[parts[i]];
    }
    // now finally change the key on that deeper object
    currentLevelData[parts[parts.length - 1]] = value;

    return finalData;
}

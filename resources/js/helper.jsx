export const arraysAreEqual = (arr1, arr2) => {
    if (arr1.length !== arr2.length) return false;
    return arr1.every((value, index) => value === arr2[index]);
}

export const productRoute = (cartItem) => {
    const params = new URLSearchParams();

    Object.entries(cartItem.option_ids)
        .forEach(([typeId, optionId]) => {
            params.append(`options[${typeId}]`, optionId+'')
        });

    return route('product.show', cartItem.slug) + '?' + params.toString();
}
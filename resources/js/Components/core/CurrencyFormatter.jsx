import React from "react";

/**
 * This component format a given amount into a currency format
 * based on the provided locale and currency
 */
const CurrencyFormatter = ({ amount, currency = "USD", locale }) => {
    return new Intl.NumberFormat(locale, {
        style : 'currency',
        currency
    }).format(amount);
};

export default CurrencyFormatter;

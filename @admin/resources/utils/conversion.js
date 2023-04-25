export const usdToToken = (usd, usdPrice) => {
  return usd / usdPrice;
}

export const tokenToUsd = (token, usdPrice) => {
  return token * usdPrice;
}

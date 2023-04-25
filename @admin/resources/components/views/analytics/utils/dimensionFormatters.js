export const date = (e) => {
  const date = new Date();
  date.setFullYear(parseInt(e.substring(0, 4)), parseInt(e.substring(4, 6)) - 1, parseInt(e.substring(6, 8)));
  return date.toLocaleDateString();
}

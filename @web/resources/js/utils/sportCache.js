export const save = (key, data) => {
  let storage = readStorage();
  storage[key] = {
    key: key,
    time: +new Date(),
    data: data
  };

  localStorage.setItem('sportCache', JSON.stringify(storage));
}

export const read = (category) => {
  const data = readStorage()[category];
  if(data && +new Date() - data.time > 1000 * 60 * 5) return null;
  return data ? data.data : null;
}

function readStorage() {
  return JSON.parse(localStorage.getItem('sportCache') ?? '{}');
}

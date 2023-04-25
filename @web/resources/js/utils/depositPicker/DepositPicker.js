export default class DepositPicker {

  id() {}

  verify(address) {}

  shouldSkipTx(txId) {
    const id = 'txCache_' + this.id();
    let cache = localStorage.getItem(id);
    if(!cache) cache = '[]';

    cache = JSON.parse(cache);

    let result = true;

    if(!cache.includes(txId)) {
      cache.push(txId);
      result = false;
    }

    localStorage.setItem(id, JSON.stringify(cache));
    return result;
  }

  startPicking(address) {
    if(this.activePickers().includes(this.id())) return;
    this.activePickers().push(this.id());

    console.log(this.logName(), address, 'Picking deposits...');

    const check = () => {
      this.verify(address);

      setTimeout(check, 15000);
    };

    check();
  }

  sendTx(txId) {
    window.axios.get('/api/walletNotify/' + this.id() + '/' + txId).then(({ data }) => {
      console.log(this.logName(), data);
    }).catch((error) => {
      console.error(this.logName(), error);
    });
  }

  logName() {
    return this.id() + '/picker »';
  }

  activePickers() {
    let pickers = window.$activePickers;

    if(!pickers) {
      window.$activePickers = [];
      return [];
    }

    return pickers;
  }

  async post(url = '', data = {}) {
    let response = await fetch(url, {
      method: 'POST',
      mode: 'cors',
      cache: 'no-cache',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json'
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      body: JSON.stringify(data)
    });
    response = response.json();

    if(response.error) throw new Error(response.error);
    return response;
  }

}

import DepositPicker from "./DepositPicker.js";

export default class extends DepositPicker {

  id() {
    return "native_sol";
  }

  verify(address) {
    this.post('https://rpc.ankr.com/solana', {
      jsonrpc: '2.0',
      id: 1,
      method: 'getSignaturesForAddress',
      params: [
        address,
        {
          limit: 10
        }
      ]
    }).then((data) => {
      console.log(this.logName(), data);
      if(data.result) data.result.forEach(e => {
        if(!this.shouldSkipTx(e.signature))
          this.sendTx(e.signature);
      });
    }).catch((e) => {
      console.error(this.logName(), e);
    });
  }

}

import DepositPicker from "./DepositPicker.js";
import { queue } from "./DepositPickerManager.js";

export default class EthereumPicker extends DepositPicker {

  constructor(instanceId, id) {
    super();
    this.instanceId = instanceId;
    this._id = id;
  }

  id() {
    return this._id;
  }

  verify(address) {
    if(!queue('ethereum').next(this.instanceId)) {
      console.log(this.logName(), 'Skipped (queue)')
      return;
    }

    this.post('https://api.etherscan.io/api?module=account&action=' + (this.id() === 'infura_eth' ? 'txlist' : 'tokentx') + '&startblock=0&endblock=999999999&address=' + address + '&sort=asc', {}).then(data => {
      console.log(this.logName(), data);
      try {
        if(typeof data.result === 'string') return;

        data.result.forEach(e => {
          if (!this.shouldSkipTx(e.hash))
            this.sendTx(e.hash);
        });
      } catch (e) {
        throw new Error(e);
      }
    }).catch((e) => {
      console.error(this.logName(), e);
    });
  }

}

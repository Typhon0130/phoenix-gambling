import SolanaPicker from "./SolanaPicker.js";
import EthereumPicker from "./EthereumPicker.js";

export const pickers = [
  new SolanaPicker(),
  new EthereumPicker(0, 'infura_eth')
];

export const startPicking = (currencyId, address) => {
  const picker = pickers.filter(e => e.id() === currencyId)[0];
  if(picker) picker.startPicking(address);
  else console.log('No picker found for ' + currencyId);
};

export const queue = (queueId) => {
  if(!window.$pickerQueue) window.$pickerQueue = [];
  if(!window.$pickerQueue[queueId]) {
    window.$pickerQueue[queueId] = {
      instances: 0,
      current: 0,

      next(callerInstanceId) {
        if (callerInstanceId > this.instances)
          this.instances = callerInstanceId;

        return this.current === callerInstanceId;
      }
    };
  }

  if(!window.$moveQueueTimer) {
    window.$moveQueueTimer = true;
    setInterval(moveQueue, 15000);
  }

  return window.$pickerQueue[queueId];
}

export const moveQueue = () => {
  if(!window.$pickerQueue) return;
  Object.values(window.$pickerQueue).forEach(queue => {
    queue.current++;
    if(queue.current > queue.instances) queue.current = 0;
  });
}

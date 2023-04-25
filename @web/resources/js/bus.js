let instance = null;

class EventBus {

  constructor() {
    if (!instance) {
      this.events = {};
      instance = this;
    }
    return instance;
  }

  $emit(event, message) {
    if (!this.events[event]) return;

    const callbacks = this.events[event];
    for (let i = 0, l = callbacks.length; i < l; i++) {
      const callback = callbacks[i];
      callback.call(this, message);
    }
  }

  $on(event, callback, once = false) {
    if (once && this.events[event]) return;
    if (!this.events[event]) this.events[event] = [];
    this.events[event].push(callback);
  }

  $retrieve(eventName, data = {}) {
    if(!this.events['retrieve:' + eventName]) this.events['retrieve:' + eventName] = data;
    else return this.events['retrieve:' + eventName](data);
  }

}

export default new EventBus();

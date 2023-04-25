import WebSocket from 'ws';

export default class WebSocketClient {

  constructor(license) {
    this.session = new WebSocket('wss://phoenix-gambling.com:2053/ws/' + license);

    this.session.onopen = () => {
      console.log(' > Connected');
    }

    this.session.onmessage = ({ data }) => {
      data = JSON.parse(data);

      if(data.error) {
        this.doNotReconnect = true;
        console.error('Phoenix Gambling server returned error: Invalid Win5X license. Exiting...');
        return;
      }

      if (process.stdout.isTTY) {
        process.stdout.clearLine(0);
        process.stdout.cursorTo(0);
        process.stdout.write("Imported " + data.tournamentName + " - " + data.home + " - " + data.away + " (" + data.matchStatus + ") " + data.scheduledTime + " " + data.status);
      } else console.log('Event', data);

      if(this.dataCallback) this.dataCallback(data);
    }

    this.session.onclose = () => {
      console.log(' > Disconnected');

      if(!this.doNotReconnect && this.disconnectCallback) this.disconnectCallback();
    }

    this.session.onerror = (e) => {
      console.log('WebSocket error:', e.message);
    }
  }

  on(dataCallback) {
    this.dataCallback = dataCallback;
  }

  onDisconnect(disconnectCallback) {
    this.disconnectCallback = () => setTimeout(disconnectCallback, 2000);
  }

}

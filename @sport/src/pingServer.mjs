import http from 'http';

http.createServer(function (request, response) {
  response.writeHead(200, {'Content-Type': "application/json"});
  response.write(JSON.stringify({
    status: true,
    info: "https://phoenix-gambling.com"
  }));
  response.end();
}).listen(9615);

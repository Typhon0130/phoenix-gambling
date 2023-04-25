#!/bin/bash
rm nohup.out
export MALLOC_ARENA_MAX=1
nohup ./litecoind -dbcache=64 -maxmempool=64 -maxconnections=5 -rpcuser=RPCUSER -rpcpassword=RPCPASSWORD -deprecatedrpc=accounts -walletnotify="/var/www/html/app/Currency/Native/WalletNotify/Unix/walletnotify.sh native_ltc %s" -blocknotify="/var/www/html/app/Currency/Native/BlockNotify/Unix/blocknotify.sh native_ltc %s" -prune=2048 & disown

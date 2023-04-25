#!/bin/bash
rm nohup.out
export MALLOC_ARENA_MAX=1
nohup ./bitcoind -dbcache=64 -maxmempool=64 -maxconnections=5 -rpcport=8445 -rpcuser=RPCUSER -deprecatedrpc=accounts -rpcpassword=RPCPASS -walletnotify="/var/www/html/app/Currency/Native/WalletNotify/Unix/walletnotify.sh native_btc %s" -blocknotify="/var/www/html/app/Currency/Native/BlockNotify/Unix/blocknotify.sh native_btc %s" -prune=2048 & disown

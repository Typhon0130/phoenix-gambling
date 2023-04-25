#!/bin/bash
rm nohup.out
export MALLOC_ARENA_MAX=1
nohup ./dogecoind -dbcache=64 -maxmempool=64 -maxconnections=5 -printtoconsole -rpcuser=RPCUSER -rpcpassword=RPCUSER -prune=2200 -walletnotify="/var/www/html/app/Currency/Native/WalletNotify/Unix/walletnotify.sh native_doge %s" -blocknotify="/var/www/html/app/Currency/Native/BlockNotify/Unix/blocknotify.sh native_doge %s" & disown

import WebSocketClient from './ws.mjs';
import './pingServer.mjs';

import axios from 'axios';

import { MongoClient } from 'mongodb';
const client = new MongoClient('mongodb://127.0.0.1:27017');

await client.connect();
const db = client.db('laravel');
const collection = db.collection('phoenix_gambling_sport_data');
const collectionHistory = db.collection('phoenix_gambling_sport_history');

await collection.deleteMany({});

const createIndexes = async (collection) => {
  await collection.createIndex({status: 1});
  await collection.createIndex({tournamentId: 1});
  await collection.createIndex({sportId: 1});
  await collection.createIndex({categoryId: 1});
}

await createIndexes(collection);
await createIndexes(collectionHistory);

if(!process.argv[2])
  throw new Error('You must provide your server URL. Example: npm run client http://localhost');

console.log('@sport module initialized');

axios.get(process.argv[2] + '/internal/license').then(({ data }) => {
  const connect = () => {
    const ws = new WebSocketClient(data.key);

    ws.on(async (data) => {
      const process = async (collection) => {
        const exists = await collection.find({srId: data.srId}).toArray();

        if (exists.length === 0) {
          collection.insertOne(data);
        } else {
          delete data['_id'];
          if(data.eSport && data.eSport.scoreboard && Object.values(data.eSport.scoreboard).length === 0) delete data.eSport['scoreboard'];

          collection.updateOne({srId: data.srId}, {$set: data});
        }
      };

      await process(collection);
      await process(collectionHistory);
    });

    ws.onDisconnect(async () => {
      await collection.deleteMany({});

      connect();
    });
  }

  connect();
}).catch((e) => {
  console.log('Failed to get Win5X license.', e);
});

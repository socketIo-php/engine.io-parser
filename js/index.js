var parser = require('engine.io-parser');

var data = new Buffer(5);
for (var i = 0; i < data.length; i++) {
    data[i] = i;
}
var decodedData = parser.decodePacket('4\uffff', false, true);
console.log(decodedData);

// parser.encodePacket({type: 'message', 'data': '\uDC00\uD834\uDF06\uDC00 \uD800\uD835\uDF07\uD800'}, null, true, function (encoded) {
//     console.log(encoded);
//     var decodedData = parser.decodePacket(encoded); // { type: 'message', data: data }
//     console.log(decodedData);
//     console.log({ type: 'message', data: '\uFFFD\uD834\uDF06\uFFFD \uFFFD\uD835\uDF07\uFFFD' });
// });

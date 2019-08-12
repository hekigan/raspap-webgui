const exec = require('child_process').exec;

exec('sudo wpa_cli -i wlan0 list_networks', (error, stdout, stderr) => {
    // console.log(stdout);
    // console.log(stderr);
    
    const lines = stdout.split('\n');
    lines.shift();

    let output = {};
    lines.forEach(line => {
    const data = line.split('\t');
    if (data.length > 1) {
        output[data[1]] = data[0];
    }
    });

    console.log({ data: output });

    if (error !== null) {
        console.log(`exec error: ${error}`);
    }
});
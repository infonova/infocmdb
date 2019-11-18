var names_to_match = [];
worker_debug = false;

function log(message) {
    if (worker_debug) {
        console.log("Worker: ");
        console.log(message);
    }
}

self.addEventListener('message', function (e) {
    var return_data = {};
    return_data.success = false;
    var message = e.data;
    return_data.type = message.type;
    log("Received message: ");
    log(message);
    switch (message.type) {
        case "setData":
            var array_length = message.data.length;
            for (var i = 0; i < array_length; i++) {
                names_to_match.push(message.data[i].filename);
            }
            return_data.success = true;
            break;
        case "checkRegex":
            var regex = message.regex;
            var result = [];
            var not_matched = [];

            log("Regex:");

            log(regex);
            var array_length = names_to_match.length;
            var result_length = 0;
            var not_matched_length = 0;

            log("Data:");
            log(names_to_match);

            for (var i = 0; i < array_length; i++) {
                var check = regex.test(names_to_match[i]);
                if (check) {
                    result.push({name: names_to_match[i], match: true});
                    result_length++;
                }
                else {
                    not_matched.push({name: names_to_match[i], match: false});
                    not_matched_length++;
                }
                if (result_length >= 5 && not_matched_length >= 5) {
                    break;
                }
            }
            return_data.success = true;
            return_data.regex = regex;
            return_data.result = result;
            return_data.not_matched = not_matched;
            log(result);
            log("Not matched: ");
            log(not_matched);
            break;
        default:
            return_data.success = false;
    }
    self.postMessage(return_data);
});
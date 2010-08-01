function belongsToClass(element, className) {
    return !!element.className && new RegExp('\\b' +
        className.replace(/\-/, '\\-') + '\\b').test(element.className);
}

function show(node) {
    node.style.display = '';
}

function hide(node) {
    node.style.display = 'none';
}

function update(node, text) {
    node.appendChild(document.createTextNode(text));
}

function clear(node) {
    while (node.hasChildNodes()) {
        node.removeChild(node.firstChild);
    }
}

function $(id) { return document.getElementById(id); }

function show$(tagname, classname) {
    elements = document.getElementsByTagName(tagname);
    for (index in elements) {
        if (belongsToClass(elements[index], classname)) {
            show(elements[index]);
        }
    }
}

function hide$(tagname, classname) {
    elements = document.getElementsByTagName(tagname);
    for (index in elements) {
        if (belongsToClass(elements[index], classname)) {
            hide(elements[index]);
        }
    }
}

function update$(tagname, classname, text) {
    elements = document.getElementsByTagName(tagname);
    for (index in elements) {
        if (belongsToClass(elements[index], classname)) {
            update(elements[index], text);
        }
    }
}

function clear$(tagname, classname) {
    elements = document.getElementsByTagName(tagname);
    for (index in elements) {
        if (belongsToClass(elements[index], classname)) {
            clear(elements[index]);
        }
    }
}

function updateInstructions(data) {
    show$('div', 'div_instructions');
    clear$('span', 'message_text');
    clear$('span', 'shortcode');
    clear$('span', 'message_cost');
    update$('span', 'message_text', [data.prefix, SERVICE].join(' '));
    update$('span', 'shortcode', data.number);
    update$('span', 'message_cost', [data.price, data.currency,
        (parseInt(data.vat)? ('('+INCLUDING_VAT+')'): ('('+WITHOUT_VAT+')'))].join(' '));
    clear$('p', 'notes');
    if (data.special) {
        update$('p', 'notes', data.special);
        show$('p', 'notes');
    } else {
        hide$('p', 'notes');
    }
}

function selectProvider(i) {
    if (i == '-') {
        hide$('div', 'div_instructions');
        return;
    }
    updateInstructions(DATA.providers[i]);
}

function selectCountry(i) {
    if (i == '-') {
        hide$('div', 'div_provider');
        hide$('div', 'div_instructions');
        return;
    }
    if (JSONResponse[i].providers && JSONResponse[i].providers.length) {
        hide$('div', 'div_instructions');
        show$('div', 'div_provider');
        DATA = JSONResponse[i];
        clear$('select', 'select_provider');
        selects = document.getElementsByTagName('select');
        for (index in selects) {
            if (belongsToClass(selects[index], 'select_provider')) {
                var def = document.createElement('option');
                update(def, 'Выберите оператора');
                def.value = '-';
                selects[index].appendChild(def);
                for (var j = 0; j < DATA.providers.length; ++j) {
                    var opt = document.createElement('option');
                    update(opt, DATA.providers[j].name);
                    opt.value = j;
                    selects[index].appendChild(opt);
                }
                selects[index].onchange = function() {
                    selectProvider(this.value);
                }
            }
        }
    }
    else {
        hide$('div', 'div_provider');
        updateInstructions(JSONResponse[i]);
    }
}

function JSONHandleResponse() {
    //document.body.style.backgroundImage = 'none';
    if (!window.JSONResponse) {
        show$('div', 'div_fail');
        return;
    }
    show$('div', 'div_ui');
    selects = document.getElementsByTagName('select');
    for (index in selects) {
        if (belongsToClass(selects[index], 'select_country')) {
            for (var i = 0; i < JSONResponse.length; ++i) {
                var opt = document.createElement('option');
                update(opt, JSONResponse[i].country_name);
                opt.value = i;
                selects[index].appendChild(opt);
            }
            selects[index].onchange = function() {
                selectCountry(this.value);
            }
        }
    }
}

function JSONSendRequest() {
    var head_node = document.getElementsByTagName('head').item(0);
    var js_node = document.createElement('script');
    js_node.src = window.JSON_URL;
    js_node.type = 'text/javascript';
    js_node.charset = 'utf-8';
    if (navigator.product == 'Gecko' || navigator.userAgent.indexOf('Opera') != -1) {
        js_node.onload = JSONHandleResponse;
    }
    else {
        js_node.onreadystatechange = function(evt) {
            evt? 1: evt = window.event;
            var rs = (evt.target || evt.srcElement).readyState;
            if (rs == 'loaded' || rs == 'complete') {
                JSONHandleResponse();
            }
        }
    }
    head_node.appendChild(js_node);
}

if (window.addEventListener) {
    window.addEventListener('load', JSONSendRequest, false);
}
else if (window.attachEvent) {
    window.attachEvent('onload', JSONSendRequest);
}

V.Ajax = function(payload = {}) {
    this.payload = payload
    this.target = window.location.href
    this.method = 'POST'
    this.contentType = 'application/x-www-form-urlencoded'

    this.execute = function(method) {
        var XHR = new XMLHttpRequest()

        // Convertimos el payload en un string
        res = [];
        for (v in this.payload) {
            // Los arrays lo tratamos distinto
            if (this.payload[v] instanceof Array) {
                for (d in this.payload[v]) {
                    res.push(v + '[]=' + encodeURIComponent(this.payload[v][d]));
                }
            } else {
                res.push (v + '=' + encodeURIComponent(this.payload[v]));
            }
        }
        var payload = res.join ('&')

        var target = this.target
        if (this.method == 'GET') {
            target += '?' + payload
        }

        // Nos fijamos si necesitamos añadir el URLBASE
        if (!/^.*:\/\//.test(target)) {
            target = V.URLBASE + target
        }

        return new Promise((resolve, reject) => {

            XHR.open(this.method, target)
            XHR.setRequestHeader('X-Vendimia-Requested-With', 'XmlHttpRequest');
            XHR.setRequestHeader('X-Vendimia-Security-Token', 
                V.e("meta[name=vendimia-security-token]").content);
            XHR.setRequestHeader('Content-Type', this.contentType);

            XHR.onreadystatechange = () => {
                if (XHR.readyState === XMLHttpRequest.DONE) {
                    if (XHR.status !== 200) {
                        reject(XHR.statusText, XHR.status)
                        return false
                    }
                    try {
                        payback = JSON.parse(XHR.responseText)
                    } catch (e) {
                        console.log (XHR.responseText)
                        reject(e.message)
                        return false
                    }
                    resolve(payback)
                }
            }
            XHR.send(payload)
        })
    }

    /**
     * Creates a payload from a form
     */
    this.fromForm = function(formName) 
    {
        elements = V.id(formName).elements
        for (i = 0; i < elements.length; i++) {
            element = elements[i]

            // Los checkboxes usan el valor checked, no value
            if (element.type.toLowerCase() == 'checkbox') {
                value = element.checked ? "1" : ""
            } else {
                value = element.value
            }

            this.payload[element.name] = value
        }

        return this
    }

    this.post = function(target) 
    {
        this.target = target
        return this.execute('POST')
    }
}

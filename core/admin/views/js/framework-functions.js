/**
 * Отправляет асинхронный запрос серверу и получает ответ от него
 *
 * @param set - параметры запроса
 * например:
 * {
 *     'url': 'site.php', // если не указать, то отправит запрос на главную страницу
 *     'type': 'POST', // метод запроса, не указать, то по умолчанию отправит get-методом
 *     'data': [], // данные запроса
 *     'headers': [] // заголовки запроса
 * }
 * @returns {Promise<unknown>}
 * @constructor
 */
const Ajax = (set) => {

    if (typeof set === 'undefined') set = {};

    if (typeof set.url === 'undefined' || !set.url){
        set.url = typeof PATH !== 'undefined' ? PATH : '/';
    }

    if (typeof set.type === 'undefined' || !set.type) set.type = 'GET';

    set.type = set.type.toUpperCase();

    let requestBody = '';

    if (typeof set.data !== 'undefined' || set.data){

        for (let i in set.data){
            requestBody += '&' + i + '=' + set.data[i];
        }

        // убираем первый '&'
        requestBody = requestBody.substr(1);

    }

    if (typeof ADMIN_MODE !== 'undefined'){

        requestBody += requestBody ? '&' : '';
        requestBody += 'ADMIN_MODE=' + ADMIN_MODE;

    }

    if (set.type === 'GET'){

        set.url += '?' + requestBody;
        requestBody = null;

    }

    /*
    * resolve - срабатывает, в случае успеха
    * reject - срабатывает, в случае ошибки
    * */
    return new Promise((resolve, reject) => {

        let request = new XMLHttpRequest();

        // открываем запрос
        request.open(set.type, set.url, true);

        // устанавливаем заголов запроса
        let contentType = false;

        if (typeof set.headers !== 'undefined' && set.headers){

            for ($i in set.headers){

                request.setRequestHeader(i, set.headers[i]);

                if (i.toLowerCase() === 'content-type') contentType = true;

            }

        }

        if (!contentType) request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        // для того чтобы сработал метод isAjax()(base/controllers/BaseMethods.php стр 86)
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        /**
         * this.status - код ответа сервера
         * this.response - тело ответа сервера
         */
        request.onload = function () {

            // если ответ сервера успешный(код в пределах 200-300)
            if (this.status >= 200 && this.status < 300){

                // если ошибка запроса(но иногда ошибки бывают и когда код в пределах 200-300)
                if (/fatal\s+?/ui.test(this.response)){
                    reject(this.response);
                }

                resolve(this.response);

            }
            
        }

        request.onerror = function () {
            reject(this.response);
        }

        request.send(requestBody);

    });

}

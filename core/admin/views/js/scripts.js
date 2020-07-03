document.querySelector('.sitemap-button').onclick = (event) => {

    // убираем типичное повидение ссылки, чтобы при клике не произошло перенаправление по адресу
    // указанному в href
    event.preventDefault();

   createSitemap();

}

let links_counter = 0;

function createSitemap() {

    links_counter++;

    Ajax({data: {ajax:'sitemap', links_counter: links_counter}})
        .then((res)=>{
            console.log('успех ' + res);
        })
        .catch((res)=>{
            console.log('ошибка ' + res);
        });

}


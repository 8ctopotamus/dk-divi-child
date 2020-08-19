// Attorney Profile PDF and Tabs
(function() {
  const { SITE_URL, attorney_name, attorney_title } = wp_data;
  const printBtn = document.getElementById('getsim-print-o-matic');
  const tabs = Array.from(document.querySelectorAll('.tablinks'));
  const tabContent = Array.from(document.querySelectorAll('.tabcontent'));
  const logo = document.querySelector('body header:first-of-type img');
  const profile = document.querySelector('.dk-random-attorney-profile');
  const attorneyIMG = profile.children[2];
  const posts = document.querySelectorAll('.dkdm_attorney_posts_bar')[1]; // NOTE: for some reason, there are 2 elements with .dkdm_attorney_posts_bar in Divi DK Mods. We are selecting the inner one here.
  const brandColor = '#829317';

  function getBase64Image(img) {
    var canvas = document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;
    var ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    var dataURL = canvas.toDataURL("image/png");
    return dataURL;
  }
  
  const mapToPDFObject = el => {
    let style = 'body';
    let type = 'text';
    let value = el.innerText;
    const tagName = el.tagName;
    switch(tagName){
      case 'H2':
        style = 'header';
        break;
      case 'H3':
        style = 'bigger';
        break;
      case 'UL':
        type = tagName.toLowerCase();
        value = [...el.children].map(c => c.innerText);
        break;
    }
    if (el.innerText === 'Main Bio') {
      value = ''
    }
    return { [type]: value, style };
  };

  const generatePDF = e => {
    e.preventDefault()

    const profileBody = [
      [...profile.children].slice(3),
      [...posts.children]
    ]
      .flat()
      .map(el => mapToPDFObject(el))

    const mainBody = [...tabContent].reduce((result, el) => {
      Array.from(el.children).map(child => result.push( mapToPDFObject(child) ));
      return result;
    }, []);
    
    const docDefinition = { 
      info: {
        title: attorney_name,
      },
      content: [
        {
          image: 'logo',
          width: 100,
          alignment: 'right',
        },
        {
          alignment: 'left',
          columns: [
            {
              stack: [
                { 
                  image: 'advisor',
                  width: 130,
                  margin: [0 ,0 ,0, 20]
                },
                ...profileBody,
              ],
              width: 130,
            },
            [
              { text: attorney_name, style: 'header' },
              { text:  attorney_title, style: 'bigger' },
              ...mainBody,
            ]
          ]
        },      
      ],
      footer: function(currentPage, pageCount) { 
        return {
          margin: [30, 40],
          height: 200,
          columns: [
            {
              image: 'logo',
              width: 80,
            },
            [
              { 
                text: 'Brookfield - Green Bay - Milwaukee',
                style: 'smaller',
                alignment: 'right',
              },
              {
                text: SITE_URL, 
                link: SITE_URL,
                style: 'smaller',
                alignment: 'right',
              },
              { 
                text: currentPage.toString() + ' of ' + pageCount,
                style: 'smaller',
                alignment: 'right',
              },
            ]
            
          ],
        }
      },
      styles: {
        header: {
          fontSize: 16,
          bold: true
        },
        bigger: {
          fontSize: 12,
          bold: true,
          color: brandColor,
        },
        body: {
          fontSize: 8.5,
          marginBottom: 10,
          // lineHeight: 1.15,
        },
        smaller: {
          fontSize: 7,
          color: '#999'
        }
      },
      defaultStyle: {
        columnGap: 20
      },
      images: {
        logo: getBase64Image(logo),
        advisor: getBase64Image(attorneyIMG),
      },
      pageMargins: [ 30, 30, 30, 100 ],
    };

    pdfMake.createPdf(docDefinition).open();
  };

  // change tabs
  function handleTabClick(evt) {
    evt.preventDefault();
    for (let i = 0; i < tabContent.length; i++) {
      tabContent[i].style.display = "none";
    }
    for (let i = 0; i < tabs.length; i++) {
      tabs[i].className = tabs[i].className.replace(" active", "");
    }
    document.getElementById(this.innerText).style.display = "block";
    evt.currentTarget.className += " active";
  };

  // attach listeners
  tabs.forEach(tab => tab.addEventListener('click', handleTabClick));
  printBtn.addEventListener('click', generatePDF);

})();
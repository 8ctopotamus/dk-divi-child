(function() {
  const closeBtns = Array.from(document.querySelectorAll('.closeButton'))

  function closeInfo(e) {
    e.preventDefault();
    const id = e.target.getAttribute('data-id')
    document.getElementById(id).className='newsByPracticeArea static';
  }

  document.getElementById("businessButton").onclick = function() {
    event.preventDefault();
    console.log('uoyoyoyo')
    document.getElementById("businessAreas").className='newsByPracticeArea active';
  }
  document.getElementById("publicButton").onclick = function() {
    event.preventDefault();
    document.getElementById("publicAreas").className='newsByPracticeArea active';
  }
  document.getElementById("individualButton").onclick = function() {
    event.preventDefault();
    document.getElementById("individualAreas").className='newsByPracticeArea active';
  }

  closeBtns.forEach(btn => btn.addEventListener('click', closeInfo))

})()
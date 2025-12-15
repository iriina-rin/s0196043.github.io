$(document).ready(function(){
    $('.multiple-items').slick({
      infinite: true,
      slidesToShow: 3,
      slidesToScroll: 2,
      dots: true,
      responsive: [
        {
            breakpoint: 719, 
            settings:{
                slidesToShow: 1, 
                slidesToScroll: 1
            }
        }
      ]
    });
});

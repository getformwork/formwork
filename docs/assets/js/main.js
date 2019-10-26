const Api = "https://api.github.com/repos/getformwork/formwork/contributors";

$(document).ready(function() {
 $.getJSON(Api)
  .done(function(data) {
    state = data;
    contributor();
  });
});

function contributor() {
  let d = state;
  let e = "";

  for (var x in d) {
    var i = d[x];
    e +='<div class="col-12 col-md-6 col-lg-4">';
    e +='<div class="card card-contributor mb-2">';
    e +='<div class="card-body p-2">';
    e +='<div class="media align-items-center">';
    e +='<img src="' + i.avatar_url + '" class="mr-3">';
    e +='<div class="media-body">';

    e +='<h5 class="mt-0 fw-5 mb-0 fs-16">';
    e +='<a href="https://github.com/' + i.login + '" target="_blank">' + i.login + '</a>';
    e +='</h5>';

    e +='<span class="text-muted fs-14">' + i.contributions + ' commits</span>';
    e +='</div>';
    e +='</div>';
    e +='</div>';
    e +='</div>';
    e +='</div>';
  }

  $("#contributors").html(e);
}
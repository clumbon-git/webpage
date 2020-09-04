//パスワード登録実行
function f_signup($form){
	$t_p = $form.pass.value;
	$t_p2 = $form.pass2.value;
	$reg = new RegExp(/^([a-zA-Z0-9]{4,30})$/);
	$t_err = false;
	$t_mes = "";

	if($t_p == ""){
		$t_err = true;
		$t_mes = "パスワードを入力してください";
	}else if($t_p != $t_p2){
		$t_err = true;
		$t_mes = "パスワードと確認が一致しません";
	}else if(!$reg.test($t_p)){
		$t_err = true;
		$t_mes = "4文字以上入力してください、使用できる文字はアルファベットと数字です";
	}

	if($t_err){
		alert($t_mes);
	}else{
		$form.submit();
	}
}

//パスワード表示
window.addEventListener('DOMContentLoaded', function(){
	var $t_p = document.getElementById('pass');
	var $t_p2 = document.getElementById('pass2');
	var $t_check = document.getElementById('passdisp');

	$t_check.addEventListener('change', function(){
		if($t_check.checked){
			$t_p.setAttribute('type', 'text');
			$t_p2.setAttribute('type', 'text');
		}else{
			$t_p.setAttribute('type', 'password');
			$t_p2.setAttribute('type', 'password');
		}
	}, false);
})

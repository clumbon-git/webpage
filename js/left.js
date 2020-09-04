//ログイン振り分け
function f_leftLogin($form, $kind){
	$t_id = $form.id_left.value;
	$t_pass = $form.pass_left.value;
	if($t_id == "" || $t_pass == ""){
		alert("id、パスワードを入力してください");
	}else{
		$form.work_flg.value="login";
		$form.kind_left.value=$kind;
		$form.submit();
	}
}

//パスワード表示
window.addEventListener('DOMContentLoaded', function(){
	var $t_check_left = false;
	var $t_pass_left = false;
	if(document.getElementById('passdisp_left') != null){
		$t_check_left = document.getElementById('passdisp_left');
	}
	if(document.getElementById('pass_left') != null){
		$t_pass_left = document.getElementById('pass_left');
	}
	if($t_check_left){
		$t_check_left.addEventListener('change', function(){
			if($t_check_left.checked){
				$t_pass_left.setAttribute('type', 'text');
			}else{
				$t_pass_left.setAttribute('type', 'password');
			}
		}, false);
	}
})

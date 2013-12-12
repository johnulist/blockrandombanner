function borrar(id){
	if(confirm("Desea borrar el banner?")){
		
		document.getElementById("deletehidden").value=id;
		document.getElementById("formdelete").submit();
	}else{
		return false;
	}
}

const beneficiarySelect =  document.getElementById('admin_reservation_beneficiary');
const classeSelect = document.getElementById('classe-select');
const classeHint = document.getElementById('classe-hint');

//initialisation au chargement avec touts les classes
function loadAllClasses(){

    classeSelect.innerHTML = '<option value="">-- Choisir une classe --</option>';

    usersData.allClasses.forEach(c => {
        classeSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        
    });

    classeHint.textContent = 'Choisissez la classe pour laquelle vous réservez.';
}

loadAllClasses();

beneficiarySelect.addEventListener("change", function(){
    const userId = this.value;

    classeSelect.innerHTML = '<option value="">-- Choisir une classe --</option>';

    if(!userId){

        //admin réserve pour lui même => à afficher toutes les classes
        loadAllClasses();

        return;
    }

    const user = usersData.users[userId];

    if(!user){
        return;
    }

    if(user.type === 'student'){
        if(user.classe) {

            //student a une seule classe
            classeSelect.innerHTML += `<option value="${user.classe.id}" selected>${user.classe.name}</option>`;
            classeHint.textContent = `Classe de l'étudiant : ${user.classe.name}`;
        }else{
            classeSelect.innerHTML += '<option value="" >Aucun classe assignée</option>';
            classeHint.textContent = "Cet étudiant n'est assigné à aucune classe.";
        }

    }else if(user.type === 'coordinator'){

        // uniquement les classes du coordinateur
        if(user.classes.length > 0){

            user.classes.forEach(c => {
                classeSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });

            classeHint.textContent = "Classes de ce coordinateur — choisissez la classe concernée.";
        }else{
            classeSelect.innerHTML += '<option value="" >Aucun classe assignée</option>';
            classeHint.textContent = "Ce coordinateur n'est assigné à aucune classe.";
        }
    }else{

        //admin ou autre => afficher toutes les classes
        loadAllClasses();
    }

})

if(selectedBeneficiaryId) {
    beneficiarySelect.value = selectedBeneficiaryId;
    beneficiarySelect.dispatchEvent(new Event('change'));
}

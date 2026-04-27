
const beneficiarySelect =  document.getElementById('coordinator_reservation_beneficiary');
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

//afficher les classes du coordinateur => ses classes
loadAllClasses();

beneficiarySelect.addEventListener("change", function(){
    const userId = this.value;

    classeSelect.innerHTML = '<option value="">-- Choisir une classe --</option>';

    if(!userId){

        //coordinateur réserve pour lui même => à afficher toutes ses classes
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
    }else{

        //=> afficher toutes ses classes
        loadAllClasses();
    }

})

// rtour après erreur - restaurer le bénéficaire sélectionné
if(selectedBeneficiaryId) {
    beneficiarySelect.value = selectedBeneficiaryId; 
    beneficiarySelect.dispatchEvent(new Event('change'));
}
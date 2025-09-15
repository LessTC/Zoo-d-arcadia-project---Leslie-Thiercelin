// 1) Mettre automatiquement l'année dans le footer
document.addEventListener("DOMContentLoaded", () => {
  const yearSpan = document.querySelector("#year");
  if (yearSpan) {
    yearSpan.textContent = new Date().getFullYear();
  }
});

// 2) Validation simple des formulaires de contact/connexion
document.addEventListener("submit", (e) => {
  const form = e.target;

  if (form.matches("form")) {
    // Vérifie que tous les champs requis sont remplis
    if (!form.checkValidity()) {
      e.preventDefault();
      alert("Merci de remplir tous les champs obligatoires.");
      return;
    }

    // Message de confirmation (pas d'envoi réel pour le moment)
    e.preventDefault();
    alert("Formulaire envoyé (simulation).");
    form.reset();
  }
});

// 3) Gestion des avis (Dashboard employé)
// Valider / invalider un avis visuellement
document.addEventListener("click", (e) => {
  const btn = e.target.closest("button");
  if (!btn) return;

  // Ligne de tableau concernée
  const row = btn.closest("tr");
  if (!row) return;

  // Si c'est un bouton "valider"
  if (btn.classList.contains("btn-success")) {
    row.classList.add("table-success");
    row.classList.remove("table-danger");
    row.dataset.status = "valide";
  }

  // Si c'est un bouton "invalider"
  if (btn.classList.contains("btn-danger")) {
    row.classList.add("table-danger");
    row.classList.remove("table-success");
    row.dataset.status = "invalide";
  }
});

// 4) Filtrer les comptes rendus (Dashboard admin / vétérinaire)
document.addEventListener("submit", (e) => {
  if (!e.target.closest("#filterAnimal") && !e.target.closest("#filterFrom")) return;

  e.preventDefault();

  const animal = document.querySelector("#filterAnimal")?.value.toLowerCase();
  const from = document.querySelector("#filterFrom")?.value;
  const to = document.querySelector("#filterTo")?.value;

  const rows = document.querySelectorAll("table tbody tr");
  rows.forEach((row) => {
    const date = row.cells[0]?.textContent.trim();
    const nomAnimal = row.cells[1]?.textContent.toLowerCase();

    let visible = true;

    if (animal && !nomAnimal.includes(animal)) {
      visible = false;
    }

    if (from && date < from) {
      visible = false;
    }

    if (to && date > to) {
      visible = false;
    }

    row.style.display = visible ? "" : "none";
  });
});

// 5) Ajout simple d’alimentation (Dashboard employé)
// Ajouter une ligne dans le tableau après soumission
document.addEventListener("submit", (e) => {
  if (!e.target.matches("#form-alimentation")) return;

  e.preventDefault();

  const form = e.target;
  const date = form.querySelector("#date")?.value;
  const heure = form.querySelector("#heure")?.value;
  const animal = form.querySelector("#animal")?.value;
  const nourriture = form.querySelector("#food")?.value;
  const quantite = form.querySelector("#quantity")?.value;

  if (!date || !heure || !animal || !nourriture || !quantite) {
    alert("Merci de remplir tous les champs.");
    return;
  }

  const tbody = document.querySelector("#table-alimentation tbody");
  if (tbody) {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${date} ${heure}</td>
      <td>${animal}</td>
      <td>${nourriture}</td>
      <td>${quantite}</td>
    `;
    tbody.appendChild(tr);
  }

  form.reset();
});

// js/main.js
document.addEventListener('DOMContentLoaded', function() {
    // --- API Endpoints ---
    const API_BASE_URL = 'api/';
    const API_JUGADORES_URL = `${API_BASE_URL}jugadores.php`;
    const API_PARTIDOS_URL = `${API_BASE_URL}partidos.php`;
    const API_EQUIPOS_URL = `${API_BASE_URL}equipos.php`;
    const API_CLUB_URL = `${API_BASE_URL}club.php`;

    // --- Common DOM Elements ---
    const getEl = (id) => document.getElementById(id);

    // Club config elements
    const formConfiguracion = getEl('form-configuracion');
    const nombreClubConfigInput = getEl('nombre-club-config'); // Este campo ahora puede ser readonly
    const logoClubConfigInput = getEl('logo-club-config');
    const clubLogoHeaderImg = getEl('club-logo-header');
    const clubNameHeaderH1 = getEl('club-name-header');
    const footerClubNameSpan = getEl('footer-club-name');

    // Modal club logos
    const logoModalEquipo = getEl('logo-modal-equipo');
    const logoModalJugador = getEl('logo-modal-jugador');
    const logoModalPartido = getEl('logo-modal-partido');
    const defaultLogoSrc = 'https://placehold.co/80x40/ffffff/cccccc?text=Logo'; 
    const defaultHeaderLogoSrc = 'https://placehold.co/100x50/ffffff/0077b6?text=Logo';

    // --- (Resto de selectores de modales, formularios, listas) ---
    const modalEquipo = getEl('modal-equipo'); // etc.
    const modalJugador = getEl('modal-jugador');
    const modalPartido = getEl('modal-partido');
    // ...otros selectores que ya tenías...


    // --- Utility Functions (fetchData, showFeedback, Modal Handling) ---
    // Estas funciones pueden permanecer como estaban en tu versión anterior,
    // ya que su lógica interna no cambia por el sistema de login.
    // fetchData:
    async function fetchData(url, action, method = 'GET', body = null) {

        const options = { method };
        let fullUrl = `${url}?action=${encodeURIComponent(action)}`;

        if (method.toUpperCase() === 'POST' && body) {
            if (body instanceof FormData) {
                options.body = body;
            } else {
                options.headers = { 'Content-Type': 'application/json' };
                options.body = JSON.stringify(body);
            }
        } else if (method.toUpperCase() === 'GET' && body && typeof body === 'object') {
            fullUrl += '&' + new URLSearchParams(body).toString();
        }
        
        try {
            const response = await fetch(fullUrl, options);
           if (!response.ok) {
			const contentType = response.headers.get("content-type");
			let errorData;

			if (contentType && contentType.includes("application/json")) {
				errorData = await response.json();
			} else {
				errorData = { message: await response.text() };
			}

			// Detectar si es un problema de sesión expirada o acceso denegado
			if (response.status === 401 || (contentType && contentType.includes("text/html"))) {
				console.warn("Posible expiración de sesión o acceso denegado, la API devolvió contenido no JSON.");
			}

			throw new Error(errorData.message || `Error ${response.status}: ${response.statusText}`);
		}

            return await response.json();
        } catch (error) {
            console.error('Fetch API Error:', error);
            showFeedback(`Error de red o servidor: ${error.message}`, 'error', document.body, true);
            return { success: false, message: error.message, data: [] };
        }
    }
    // showFeedback:
    function showFeedback(message, type, container = document.body, isGlobal = false) {
        const feedbackDiv = document.createElement('div');
        feedbackDiv.className = `feedback-message ${type} p-3 sm:p-4 mb-4 text-xs sm:text-sm rounded-lg fixed top-5 right-5 z-[100] shadow-lg`;
        if (type === 'success') {
            feedbackDiv.classList.add('bg-green-100', 'text-green-700');
        } else { 
            feedbackDiv.classList.add('bg-red-100', 'text-red-700');
        }
        feedbackDiv.textContent = message;
        document.querySelectorAll('.feedback-message.fixed').forEach(el => el.remove());
        if (isGlobal || container === document.body || !document.body.contains(container)) {
             document.body.prepend(feedbackDiv);
        } else {
            container.prepend(feedbackDiv);
        }
        setTimeout(() => { if (feedbackDiv.parentNode) feedbackDiv.remove(); }, 5000);
    }
    // Modal Handling (openModal, closeModal, setupModal):
    function openModal(modalElement) { /* ... tu código ... */ if (modalElement) modalElement.style.display = 'block'; }
    function closeModal(modalElement) { /* ... tu código ... */ if (modalElement) modalElement.style.display = 'none'; }
    function setupModal(modalId, openBtnId, closeBtnId, formId, titleId, titleText) {
        const modal = getEl(modalId); const openBtn = getEl(openBtnId); const closeBtn = getEl(closeBtnId); const form = getEl(formId); const titleEl = getEl(titleId);
        if (openBtn) { openBtn.addEventListener('click', () => { if (form) form.reset(); if (form && form.elements.id) form.elements.id.value = ''; if (titleEl) titleEl.textContent = titleText; openModal(modal); }); }
        if (closeBtn) closeBtn.addEventListener('click', () => closeModal(modal));
        return { modal, form };
    }
    const { modal: eqModal, form: eqForm } = setupModal('modal-equipo', 'abrir-modal-equipo', 'cerrar-modal-equipo', 'form-equipo', 'modal-titulo-equipo', 'Añadir Equipo');
    const { modal: jugModal, form: jugForm } = setupModal('modal-jugador', 'abrir-modal-jugador', 'cerrar-modal-jugador', 'form-jugador', 'modal-titulo-jugador', 'Añadir Jugador');
    const { modal: parModal, form: parForm } = setupModal('modal-partido', 'abrir-modal-partido', 'cerrar-modal-partido', 'form-partido', 'modal-titulo-partido', 'Añadir Partido');
    window.addEventListener('click', (event) => { if (event.target === eqModal) closeModal(eqModal); if (event.target === jugModal) closeModal(jugModal); if (event.target === parModal) closeModal(parModal); });


    // --- Club Configuration Section ---
    async function cargarConfiguracionClub() {
        const response = await fetch(API_CLUB_URL + "?action=get_config")
    .then(response => response.text())  // Obtiene el texto sin procesar
    .then(data => console.log("Respuesta del servidor:", data)); // Muestra lo recibido

        let clubName = 'Gestión de Minibasket'; 
        let logoUrl = ''; 

        if (response && response.success && response.data) {
            clubName = response.data.nombre || clubName;
            logoUrl = (response.data.logotipo && response.data.logotipo.trim() !== '') ? response.data.logotipo.trim() : '';
            
            // El campo nombreClubConfigInput ya se renderiza como readonly o no por PHP.
            // Solo actualizamos su valor.
            if (nombreClubConfigInput) nombreClubConfigInput.value = response.data.nombre || '';
            if (logoClubConfigInput) logoClubConfigInput.value = response.data.logotipo || '';

        } else if (response && !response.success && response.data && response.data.nombre) { 
             clubName = response.data.nombre;
             logoUrl = (response.data.logotipo && response.data.logotipo.trim() !== '') ? response.data.logotipo.trim() : '';
             if (nombreClubConfigInput) nombreClubConfigInput.value = clubName;
             if (logoClubConfigInput) logoClubConfigInput.value = logoUrl;
        } else {
            console.warn("No se pudo cargar la configuración del club, usando valores por defecto.");
            if (nombreClubConfigInput) nombreClubConfigInput.value = clubName; // Establece el valor por defecto en el campo
            if (logoClubConfigInput) logoClubConfigInput.value = logoUrl; // Establece el valor por defecto en el campo
        }

        if (clubNameHeaderH1) clubNameHeaderH1.textContent = clubName;
        if (clubLogoHeaderImg) {
            if (logoUrl) {
                clubLogoHeaderImg.src = logoUrl;
                clubLogoHeaderImg.style.display = 'block';
                clubLogoHeaderImg.onerror = function() { this.src = defaultHeaderLogoSrc; this.style.display = 'block'; };
            } else {
                clubLogoHeaderImg.src = defaultHeaderLogoSrc;
                clubLogoHeaderImg.style.display = 'block';
            }
        }
        if (footerClubNameSpan) footerClubNameSpan.textContent = clubName;
        const modalLogos = [logoModalEquipo, logoModalJugador, logoModalPartido];
        modalLogos.forEach(imgEl => {
            if (imgEl) {
                if (logoUrl) {
                    imgEl.src = logoUrl;
                    imgEl.style.display = 'block';
                    imgEl.onerror = function() { this.src = defaultLogoSrc; this.style.display = 'block'; };
                } else {
                    imgEl.style.display = 'none';
                }
            }
        });
    }

    if (formConfiguracion) {
        formConfiguracion.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData();
            const configSection = getEl('configuracion');

            // Solo añadir el nombre del club al FormData si el campo NO es readonly
            // El backend (api/club.php) también verificará el rol de admin para el nombre.
            if (nombreClubConfigInput && !nombreClubConfigInput.readOnly) {
                formData.append('nombre_club_config', nombreClubConfigInput.value);
            }
            // El logo siempre se envía, ya que asumimos que cualquiera (logueado) puede cambiarlo.
            // Si también quieres restringir el logo al admin, aplica una lógica similar.
            if (logoClubConfigInput) {
                 formData.append('logo_club_config', logoClubConfigInput.value);
            }

            // Verificar si hay algo que enviar, aparte de la acción
            let hasDataToSend = false;
            for (let pair of formData.entries()) {
                if (pair[0] !== 'action') { // 'action' se añade en fetchData
                    hasDataToSend = true;
                    break;
                }
            }
             if (!hasDataToSend && nombreClubConfigInput && nombreClubConfigInput.readOnly) {
                // Si solo se envió el logo y estaba vacío, o si el nombre es readonly y no hay logo
                // Podríamos no hacer la llamada o manejarla específicamente.
                // Por ahora, si el nombre es readonly y el logo está vacío, no se envía nada útil.
                // La API de PHP maneja el caso de que solo se actualice el logo.
             }


            const response = await fetchData(API_CLUB_URL, 'update_config', 'POST', formData);

            if (response && response.success) {
                showFeedback(response.message || 'Configuración guardada.', 'success', configSection);
                if (response.data) {
                    await cargarConfiguracionClub(); // Recargar y aplicar cambios
                }
            } else {
                showFeedback(response.message || 'Error al guardar configuración.', 'error', configSection);
            }
        });
    }

    // --- Funciones cargarEquipos, cargarJugadores, cargarPartidos ---
    // Y sus respectivos event listeners para los formularios de añadir
    // (pueden permanecer como estaban, ya que ahora index.php ya está protegido por login)
    async function cargarEquipos() { /* ... tu código existente ... */ }
    if (getEl('form-equipo')) { getEl('form-equipo').addEventListener('submit', async (event) => { /* ... tu código ... */ }); }
    
    async function cargarJugadores() { /* ... tu código existente ... */ }
    if (getEl('form-jugador')) { getEl('form-jugador').addEventListener('submit', async (event) => { /* ... tu código ... */ }); }

    async function cargarPartidos() { /* ... tu código existente ... */ }
    if (getEl('form-partido')) { getEl('form-partido').addEventListener('submit', async (event) => { /* ... tu código ... */ }); }


    // --- Initial Data Load ---
    async function initApp() {
        await cargarConfiguracionClub();
        // Estas funciones ahora se ejecutarán solo si el usuario está logueado (verificación en index.php)
        if(getEl('lista-equipos-container')) cargarEquipos(); // Verifica si el elemento existe antes de llamar
        if(getEl('lista-jugadores')) cargarJugadores();
        if(getEl('lista-partidos')) cargarPartidos();
    }

    initApp();
});

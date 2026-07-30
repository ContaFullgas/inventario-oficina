<?php
// Archivo: includes/footer.php
?>

<style>
/* ==========================================================================
   FOOTER FULLGAS — Estilo Ataraxis adaptado
   Ahora usa las variables de tema (--bg-body, --text-dark, --text-muted,
   --border-color, --primary) para que cambie automáticamente entre
   modo claro y oscuro, igual que el resto del sitio.
   ========================================================================== */

/* Sobrescribe el fondo fijo que index.php le puso a .footer-fullgas,
   para que reaccione al tema en vez de quedar siempre en navy oscuro */
.footer-fullgas {
    background-color: var(--bg-body) !important;
    position: relative;
    overflow: hidden;
}

/* Patrón de líneas geométricas sutiles de fondo */
.footer-lines {
    position: absolute;
    inset: 0;
    opacity: 0.08;
    pointer-events: none;
}

.footer-inner {
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 1;
}

.footer-grid-model {
    display: grid;
    grid-template-columns: 5fr 2fr 5fr;
    gap: 2.5rem;
    align-items: center;
    padding-bottom: 2.5rem;
}

/* ---- Columna 1: Marca + Jefe directo ---- */
.footer-brand-title {
    font-size: 1.4rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: var(--text-dark);
    margin-bottom: 1.5rem;
}

.footer-boss-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.25rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.footer-boss-kicker {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--primary);
    display: block;
    margin-bottom: 12px;
}

.footer-boss-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.footer-boss-avatar {
    width: 44px;
    height: 44px;
    min-width: 44px;
    border-radius: 50%;
    background: var(--primary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 17px;
}

.footer-boss-name {
    font-weight: 700;
    color: var(--text-dark);
    font-size: 15px;
    margin: 0;
}

.footer-boss-role {
    font-size: 12px;
    color: var(--text-muted);
    margin: 2px 0 0;
}

.footer-boss-mail {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: var(--primary);
    color: #fff !important;
    font-weight: 600;
    font-size: 13.5px;
    padding: 10px 16px;
    border-radius: 10px;
    text-decoration: none;
    transition: background 0.2s, transform 0.2s;
}

.footer-boss-mail:hover {
    background: var(--primary-hover);
    color: #fff;
    transform: translateY(-2px);
}

/* ---- Columna 2: Logo + Volver arriba ---- */
.footer-mid-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
}

.footer-mid-col img {
    max-width: 120px;
    opacity: 0.95;
}

.footer-back-top {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    color: var(--primary);
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.04em;
    padding: 10px 18px;
    border-radius: 50px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.footer-back-top:hover {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
}

/* ---- Columna 3: Equipo de soporte ---- */
.footer-team-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.footer-team-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 12px;
    margin-bottom: 14px;
}

.footer-team-head span:first-child {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--primary);
}

.footer-team-head span:last-child {
    font-size: 11.5px;
    color: var(--text-muted);
}

.footer-team-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.footer-team-row {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    padding: 9px 10px;
    border-radius: 10px;
    transition: border-color 0.2s;
    text-decoration:none;
    color:inherit;
}

.footer-team-row:hover {
    border-color: var(--primary);
}

.footer-team-row-avatar {
    width: 26px;
    height: 26px;
    min-width: 26px;
    border-radius: 50%;
    background: var(--primary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 11px;
}

.footer-team-row span {
    font-size: 13px;
    font-weight: 500;
    color: var(--text-main);
}

/* ---- Barra inferior ---- */
.footer-bottom-bar {
    background: var(--primary);
    color: #f0fdfa;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-align: center;
    padding: 0.85rem 1rem;
}

/* ---- Responsivo ---- */
@media (max-width: 900px) {
    .footer-grid-model {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .footer-boss-row {
        justify-content: center;
    }

    .footer-mid-col {
        order: -1;
    }
}
</style>

<footer class="footer-fullgas">

    <svg class="footer-lines" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" preserveAspectRatio="none">
        <path d="M0,200 L1200,50 M300,400 L900,0 M600,300 L1200,400" stroke="#0d9488" stroke-width="1.5" fill="none" />
    </svg>

    <div class="footer-inner">
        <div class="footer-grid-model">

            <!-- Columna 1: Marca + Jefe directo -->
            <div>
                <div class="footer-brand-title">FULL GAS</div>

                <div class="footer-boss-card">
                    <span class="footer-boss-kicker">Jefe directo</span>
                    <div class="footer-boss-row">
                        <div class="footer-boss-avatar"><i class="fa-solid fa-user-tie"></i></div>
                        <div>
                            <p class="footer-boss-name">Ing. Ismael Santiago</p>
                            <p class="footer-boss-role">Supervisor de operaciones</p>
                        </div>
                    </div>
                    <a href="mailto:ismael.santiago@fullgas.com.mx" class="footer-boss-mail">
                        <i class="fa-solid fa-envelope"></i> Enviar correo a Ismael
                    </a>
                </div>
            </div>

            <!-- Columna 2: Logo + Volver arriba -->
            <div class="footer-mid-col">
                <img src="assets/logo_FG.png" alt="Fullgas">
                <a href="#" class="footer-back-top" onclick="window.scrollTo({top:0, behavior:'smooth'}); return false;">
                    <i class="fa-solid fa-arrow-up"></i> VOLVER AL INICIO
                </a>
            </div>

            <!-- Columna 3: Equipo de soporte -->
            <div class="footer-team-card">
                <div class="footer-team-head">
                    <span>Equipo de soporte</span>
                    <span>5 integrantes</span>
                </div>
                <div class="footer-team-list">
                    <a href="mailto:contabsistemas3@fullgas.com.mx" class="footer-team-row">
                        <div class="footer-team-row-avatar"><i class="fa-solid fa-user"></i></div>
                        <span>M. E. Sergio Leon</span>
                    </a>
                    <a href="mailto:contabsistemas7@fullgas.com.mx" class="footer-team-row">
                        <div class="footer-team-row-avatar"><i class="fa-solid fa-user"></i></div>
                        <span>Ing. Yaneli Cel</span>
                    </a>
                    <a href="mailto:contabsistemas1@fullgas.com.mx" class="footer-team-row">
                        <div class="footer-team-row-avatar"><i class="fa-solid fa-user"></i></div>
                        <span>Ing. Aldo Ventura</span>
                    </a>
                    <a href="mailto:contabsistemas8@fullgas.com.mx" class="footer-team-row">
                        <div class="footer-team-row-avatar"><i class="fa-solid fa-user"></i></div>
                        <span>I.S.C. Isaac Varguez</span>
                    </a>
                    <a href="mailto:contabsistemas5@fullgas.com.mx" class="footer-team-row">
                        <div class="footer-team-row-avatar"><i class="fa-solid fa-user"></i></div>
                        <span>Ing. Leonardo Dzul</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <div class="footer-bottom-bar">
        Copyright &copy; <?= date('Y') ?> FullGas. Todos los Derechos Reservados.
    </div>

</footer>



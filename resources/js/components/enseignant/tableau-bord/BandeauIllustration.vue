<script setup lang="ts">
// Illustration de la maquette A (issue #35) : un aîné en fauteuil roulant
// échange avec une enseignante et un étudiant, à côté du titre CCEG.

const rayons = Array.from({ length: 8 }, (_, k) => {
    const angle = (k * Math.PI) / 4;

    return {
        x2: (420 + 36 * Math.cos(angle)).toFixed(1),
        y2: (255 + 36 * Math.sin(angle)).toFixed(1),
    };
});
</script>

<template>
    <div class="bandeau overflow-hidden rounded-xl border bg-card">
        <svg
            class="block h-auto w-full"
            viewBox="0 0 1200 340"
            preserveAspectRatio="xMidYMid meet"
            role="img"
            :aria-label="$t('enseignant.index.dashboard.illustration_alt')"
        >
            <defs>
                <linearGradient id="bandeau-ciel" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" style="stop-color: var(--il-ciel1)" />
                    <stop offset="1" style="stop-color: var(--il-ciel2)" />
                </linearGradient>
                <radialGradient id="bandeau-halo" cx=".5" cy=".5" r=".5">
                    <stop
                        offset="0"
                        style="stop-color: var(--il-halo); stop-opacity: 0.55"
                    />
                    <stop
                        offset="1"
                        style="stop-color: var(--il-halo); stop-opacity: 0"
                    />
                </radialGradient>
            </defs>
            <rect width="1200" height="340" fill="url(#bandeau-ciel)" />
            <circle cx="410" cy="190" r="230" fill="url(#bandeau-halo)" />

            <!-- Cadres souvenirs au mur -->
            <g opacity=".85">
                <rect
                    x="58"
                    y="44"
                    width="58"
                    height="46"
                    rx="4"
                    class="accent"
                    opacity=".25"
                />
                <rect
                    x="64"
                    y="50"
                    width="46"
                    height="34"
                    rx="2"
                    fill="#fff"
                    opacity=".7"
                />
                <path
                    d="M66 82 L80 66 L90 76 L98 68 L108 82 Z"
                    class="accent"
                    opacity=".6"
                />
                <rect
                    x="268"
                    y="36"
                    width="44"
                    height="54"
                    rx="4"
                    class="accent"
                    opacity=".25"
                />
                <rect
                    x="273"
                    y="41"
                    width="34"
                    height="44"
                    rx="2"
                    fill="#fff"
                    opacity=".7"
                />
                <circle cx="290" cy="58" r="7" class="accent" opacity=".5" />
                <path
                    d="M279 82 Q290 66 301 82 Z"
                    class="accent"
                    opacity=".5"
                />
                <rect
                    x="318"
                    y="52"
                    width="36"
                    height="28"
                    rx="3"
                    class="accent"
                    opacity=".25"
                />
                <rect
                    x="322"
                    y="56"
                    width="28"
                    height="20"
                    rx="2"
                    fill="#fff"
                    opacity=".7"
                />
            </g>
            <rect
                y="300"
                width="1200"
                height="40"
                style="fill: var(--il-sol)"
            />
            <rect
                y="298"
                width="1200"
                height="3"
                class="accent"
                opacity=".25"
            />

            <!-- Enseignante -->
            <ellipse
                cx="190"
                cy="301"
                rx="44"
                ry="6"
                fill="#000"
                opacity=".12"
            />
            <rect
                x="177"
                y="228"
                width="12"
                height="70"
                rx="5"
                fill="#2b3245"
            />
            <rect
                x="193"
                y="228"
                width="12"
                height="70"
                rx="5"
                fill="#2b3245"
            />
            <ellipse cx="185" cy="298" rx="11" ry="5" fill="#1f1f1f" />
            <ellipse cx="203" cy="298" rx="11" ry="5" fill="#1f1f1f" />
            <path
                d="M160 150 Q190 135 220 150 L226 238 Q190 246 154 238 Z"
                style="fill: var(--il-veston)"
            />
            <path d="M181 145 L190 170 L199 145 Z" fill="#f8fafc" />
            <rect
                x="184"
                y="128"
                width="12"
                height="16"
                rx="3"
                fill="#e8b48f"
            />
            <circle cx="190" cy="112" r="21" fill="#e8b48f" />
            <circle cx="171" cy="96" r="10" fill="#5a3825" />
            <path
                d="M169 114 Q166 88 190 89 Q213 90 211 110 Q206 98 191 99 Q178 100 169 114Z"
                fill="#5a3825"
            />
            <circle cx="199" cy="112" r="2.2" fill="#2a2a2a" />
            <path
                d="M196 121 Q201 125 206 120"
                stroke="#8a4b33"
                stroke-width="2"
                fill="none"
                stroke-linecap="round"
            />
            <rect
                x="204"
                y="166"
                width="26"
                height="34"
                rx="3"
                fill="#1e293b"
                transform="rotate(-12 217 183)"
            />
            <rect
                x="208"
                y="170"
                width="18"
                height="24"
                rx="1"
                fill="#93c5fd"
                transform="rotate(-12 217 183)"
            />
            <path
                d="M212 158 Q228 178 214 192"
                style="stroke: var(--il-veston)"
                stroke-width="13"
                fill="none"
                stroke-linecap="round"
            />
            <circle cx="214" cy="193" r="6.5" fill="#e8b48f" />

            <!-- Aîné en fauteuil roulant -->
            <ellipse
                cx="440"
                cy="302"
                rx="78"
                ry="7"
                fill="#000"
                opacity=".12"
            />
            <g stroke="#3b4252" stroke-linecap="round" fill="none">
                <path d="M373 136 L383 222" stroke-width="6" />
                <path d="M373 136 L356 133" stroke-width="6" />
                <path d="M462 222 L486 284" stroke-width="5" />
                <path d="M478 287 L508 287" stroke-width="6" />
                <path d="M462 222 L470 279" stroke-width="4" />
                <circle cx="470" cy="290" r="10" stroke-width="5" />
            </g>
            <path
                d="M396 205 Q384 188 390 168"
                stroke="#3b4252"
                stroke-width="5"
                fill="none"
                stroke-linecap="round"
            />
            <rect x="378" y="214" width="92" height="9" rx="4" fill="#3b4252" />
            <path
                d="M384 152 Q408 140 432 152 L438 216 L382 216 Z"
                fill="#7b4f93"
            />
            <path
                d="M392 196 L470 198 Q476 212 467 223 L392 223 Z"
                fill="#c9a040"
            />
            <path
                d="M410 197 L410 223 M430 197.5 L430 223 M450 198 L450 223"
                stroke="#a37f2a"
                stroke-width="2.5"
            />
            <path d="M392 208 L471 210" stroke="#a37f2a" stroke-width="2.5" />
            <path d="M452 210 L474 210 L490 279 L468 279 Z" fill="#4a4f63" />
            <ellipse cx="489" cy="282" rx="15" ry="6" fill="#1f1f1f" />
            <g stroke="#3b4252" fill="none" stroke-linecap="round">
                <circle cx="420" cy="255" r="44" stroke-width="7" />
                <circle
                    cx="420"
                    cy="255"
                    r="36"
                    stroke="#9aa3b5"
                    stroke-width="2"
                />
                <g stroke="#9aa3b5" stroke-width="1.6">
                    <line
                        v-for="(rayon, i) in rayons"
                        :key="i"
                        x1="420"
                        y1="255"
                        :x2="rayon.x2"
                        :y2="rayon.y2"
                    />
                </g>
            </g>
            <circle cx="420" cy="255" r="5.5" fill="#3b4252" />
            <rect
                x="403"
                y="127"
                width="12"
                height="15"
                rx="3"
                fill="#d9a07b"
            />
            <circle cx="410" cy="111" r="21" fill="#d9a07b" />
            <path
                d="M389 113 Q387 88 410 88 Q433 88 431 110 Q427 97 411 97 Q395 98 389 113 Z"
                fill="#e5e5e5"
            />
            <path
                d="M390 116 Q386 104 391 98"
                stroke="#e5e5e5"
                stroke-width="4"
                fill="none"
                stroke-linecap="round"
            />
            <circle
                cx="420"
                cy="110"
                r="5.5"
                stroke="#2a2a2a"
                stroke-width="1.8"
                fill="rgba(255,255,255,.35)"
            />
            <path d="M414.5 110 L401 108" stroke="#2a2a2a" stroke-width="1.6" />
            <path
                d="M416 121 Q421 125 427 120"
                stroke="#8a4b33"
                stroke-width="2"
                fill="none"
                stroke-linecap="round"
            />
            <path
                d="M424 160 Q450 176 465 158"
                stroke="#7b4f93"
                stroke-width="13"
                fill="none"
                stroke-linecap="round"
            />
            <circle cx="468" cy="155" r="7" fill="#d9a07b" />

            <!-- Étudiant -->
            <ellipse
                cx="612"
                cy="301"
                rx="44"
                ry="6"
                fill="#000"
                opacity=".12"
            />
            <rect
                x="628"
                y="150"
                width="24"
                height="58"
                rx="9"
                fill="#d9663f"
            />
            <rect
                x="598"
                y="228"
                width="12"
                height="70"
                rx="5"
                fill="#3d5a80"
            />
            <rect
                x="614"
                y="228"
                width="12"
                height="70"
                rx="5"
                fill="#3d5a80"
            />
            <ellipse
                cx="601"
                cy="298"
                rx="12"
                ry="5"
                fill="#f8fafc"
                stroke="#cbd5e1"
            />
            <ellipse
                cx="620"
                cy="298"
                rx="12"
                ry="5"
                fill="#f8fafc"
                stroke="#cbd5e1"
            />
            <path
                d="M583 152 Q611 138 639 152 L643 238 Q611 246 579 238 Z"
                fill="#2a9d8f"
            />
            <path
                d="M596 145 Q611 156 626 145"
                stroke="#1f7a6f"
                stroke-width="4"
                fill="none"
                stroke-linecap="round"
            />
            <path
                d="M605 160 L605 182 M617 160 L617 182"
                stroke="#e9f5f3"
                stroke-width="2"
                stroke-linecap="round"
            />
            <rect
                x="605"
                y="127"
                width="12"
                height="15"
                rx="3"
                fill="#a86b47"
            />
            <circle cx="611" cy="114" r="20" fill="#a86b47" />
            <g fill="#1f1410">
                <circle cx="596" cy="102" r="8" />
                <circle cx="606" cy="95" r="9" />
                <circle cx="618" cy="95" r="9" />
                <circle cx="627" cy="103" r="8" />
                <circle cx="628" cy="113" r="6" />
            </g>
            <circle cx="601" cy="114" r="2.2" fill="#1f1410" />
            <path
                d="M596 123 Q601 127 606 123"
                stroke="#5a2f1c"
                stroke-width="2"
                fill="none"
                stroke-linecap="round"
            />
            <rect
                x="566"
                y="170"
                width="24"
                height="31"
                rx="2"
                fill="#fff"
                stroke="#94a3b8"
                transform="rotate(8 578 185)"
            />
            <path
                d="M571 178 H585 M571 184 H585 M571 190 H581"
                stroke="#94a3b8"
                stroke-width="1.5"
                transform="rotate(8 578 185)"
            />
            <path
                d="M593 160 Q576 178 584 192"
                stroke="#2a9d8f"
                stroke-width="13"
                fill="none"
                stroke-linecap="round"
            />
            <circle cx="585" cy="193" r="6.5" fill="#a86b47" />

            <!-- Bulles de dialogue -->
            <g>
                <rect
                    x="466"
                    y="38"
                    width="124"
                    height="46"
                    rx="14"
                    fill="#fff"
                    opacity=".96"
                />
                <path
                    d="M484 82 L474 102 L504 83 Z"
                    fill="#fff"
                    opacity=".96"
                />
                <text
                    x="528"
                    y="67"
                    text-anchor="middle"
                    font-family="Instrument Serif, serif"
                    font-style="italic"
                    font-size="19"
                    fill="#3b2a12"
                >
                    « En 1962… »
                </text>
            </g>
            <g>
                <rect
                    x="610"
                    y="44"
                    width="100"
                    height="36"
                    rx="12"
                    fill="#fff"
                    opacity=".96"
                />
                <path d="M626 79 L620 96 L644 80 Z" fill="#fff" opacity=".96" />
                <text
                    x="660"
                    y="67"
                    text-anchor="middle"
                    font-family="Instrument Sans, sans-serif"
                    font-size="13.5"
                    font-weight="600"
                    fill="#1f7a6f"
                >
                    Et ensuite ?
                </text>
            </g>
            <path
                d="M150 70 c-4-6-12-2-8 4 l8 8 8-8 c4-6-4-10-8-4z"
                class="accent"
                opacity=".7"
            />

            <!-- Titre CCEG -->
            <line
                x1="770"
                y1="78"
                x2="770"
                y2="262"
                style="stroke: var(--il-accent)"
                stroke-width="2"
                opacity=".5"
            />
            <text
                x="1164"
                y="205"
                text-anchor="end"
                font-family="Instrument Sans, sans-serif"
                font-size="140"
                font-weight="700"
                letter-spacing="2"
                style="fill: var(--il-texte)"
            >
                CCEG
            </text>
            <text
                x="1160"
                y="250"
                text-anchor="end"
                font-family="Instrument Serif, serif"
                font-style="italic"
                font-size="32"
                style="fill: var(--il-sous-titre)"
            >
                Votre histoire, notre histoire
            </text>
        </svg>
    </div>
</template>

<style scoped>
.bandeau {
    --il-ciel1: #f6efe1;
    --il-ciel2: #e7eefb;
    --il-sol: #e8dfcc;
    --il-texte: #171717;
    --il-sous-titre: #6b5a2e;
    --il-accent: #c9a040;
    --il-veston: #334155;
    --il-halo: #ffffff;
}

/* En mode sombre, on reprend la palette nuit/or du musée pour garder le contraste du titre */
:global(.dark) .bandeau {
    --il-ciel1: #191a3c;
    --il-ciel2: #0f1e30;
    --il-sol: #22233f;
    --il-texte: #e8d5a3;
    --il-sous-titre: #c9a040;
    --il-veston: #5b6b8f;
    --il-halo: #c9a040;
}

.accent {
    fill: var(--il-accent);
}
</style>

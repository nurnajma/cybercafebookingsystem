<?php
// ── AVATAR DEFINITIONS ────────────────────────────────────────
// Each avatar has a key (stored in DB), emoji, gradient, and label
function getAvatars(): array {
    return [
        'gamepad'   => ['emoji'=>'🎮', 'gradient'=>'linear-gradient(135deg,#7c3aed,#4f46e5)', 'label'=>'Gamer'],
        'skull'     => ['emoji'=>'💀', 'gradient'=>'linear-gradient(135deg,#dc2626,#7f1d1d)', 'label'=>'Skull'],
        'dragon'    => ['emoji'=>'🐉', 'gradient'=>'linear-gradient(135deg,#16a34a,#065f46)', 'label'=>'Dragon'],
        'robot'     => ['emoji'=>'🤖', 'gradient'=>'linear-gradient(135deg,#0891b2,#1e40af)', 'label'=>'Robot'],
        'ninja'     => ['emoji'=>'🥷', 'gradient'=>'linear-gradient(135deg,#374151,#111827)', 'label'=>'Ninja'],
        'alien'     => ['emoji'=>'👾', 'gradient'=>'linear-gradient(135deg,#7c3aed,#be185d)', 'label'=>'Alien'],
        'ghost'     => ['emoji'=>'👻', 'gradient'=>'linear-gradient(135deg,#6b7280,#374151)', 'label'=>'Ghost'],
        'wizard'    => ['emoji'=>'🧙', 'gradient'=>'linear-gradient(135deg,#7e22ce,#1e3a8a)', 'label'=>'Wizard'],
        'fire'      => ['emoji'=>'🔥', 'gradient'=>'linear-gradient(135deg,#ea580c,#dc2626)', 'label'=>'Fire'],
        'lightning' => ['emoji'=>'⚡', 'gradient'=>'linear-gradient(135deg,#ca8a04,#d97706)', 'label'=>'Lightning'],
        'crown'     => ['emoji'=>'👑', 'gradient'=>'linear-gradient(135deg,#b45309,#92400e)', 'label'=>'Crown'],
        'trophy'    => ['emoji'=>'🏆', 'gradient'=>'linear-gradient(135deg,#d97706,#92400e)', 'label'=>'Trophy'],
        'target'    => ['emoji'=>'🎯', 'gradient'=>'linear-gradient(135deg,#e11d48,#9f1239)', 'label'=>'Target'],
        'bomb'      => ['emoji'=>'💣', 'gradient'=>'linear-gradient(135deg,#1f2937,#111827)', 'label'=>'Bomb'],
        'sword'     => ['emoji'=>'⚔️', 'gradient'=>'linear-gradient(135deg,#475569,#1e293b)', 'label'=>'Warrior'],
        'alien2'    => ['emoji'=>'🛸', 'gradient'=>'linear-gradient(135deg,#0e7490,#7c3aed)', 'label'=>'UFO'],
    ];
}

// ── RENDER AVATAR ─────────────────────────────────────────────
function renderAvatar(string $key, string $size = 'md', string $extraStyle = ''): string {
    $avatars = getAvatars();
    $av      = $avatars[$key] ?? $avatars['gamepad'];

    $sizes = [
        'xs' => ['box'=>'28px',  'font'=>'0.75rem', 'radius'=>'6px'],
        'sm' => ['box'=>'34px',  'font'=>'0.9rem',  'radius'=>'7px'],
        'md' => ['box'=>'44px',  'font'=>'1.2rem',  'radius'=>'9px'],
        'lg' => ['box'=>'64px',  'font'=>'1.8rem',  'radius'=>'12px'],
        'xl' => ['box'=>'88px',  'font'=>'2.4rem',  'radius'=>'16px'],
    ];
    $s = $sizes[$size] ?? $sizes['md'];

    return sprintf(
        '<div style="width:%s;height:%s;border-radius:%s;background:%s;display:flex;align-items:center;justify-content:center;font-size:%s;flex-shrink:0;%s" title="%s">%s</div>',
        $s['box'], $s['box'], $s['radius'],
        $av['gradient'],
        $s['font'],
        $extraStyle,
        htmlspecialchars($av['label']),
        $av['emoji']
    );
}

// ── AVATAR PICKER MODAL HTML ──────────────────────────────────
// Include this once on any page that needs the picker
function renderAvatarPickerModal(string $currentKey, string $apiPath = '/try_harder_v3/api_avatar.php'): string {
    $avatars = getAvatars();
    $grid = '';
    foreach ($avatars as $key => $av) {
        $selected = ($key === $currentKey) ? 'border:2px solid #fff;transform:scale(1.1);' : 'border:2px solid transparent;';
        $grid .= sprintf(
            '<div class="avatar-option" data-key="%s" onclick="selectAvatar(this)"
                style="cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:.4rem;padding:.5rem;border-radius:8px;transition:all .2s;">
                <div style="width:52px;height:52px;border-radius:10px;background:%s;display:flex;align-items:center;justify-content:center;font-size:1.5rem;%s">%s</div>
                <span style="font-size:.65rem;color:#9090b0;letter-spacing:.5px;text-transform:uppercase">%s</span>
            </div>',
            $key, $av['gradient'], $selected, $av['emoji'], $av['label']
        );
    }

    return '
    <div id="avatar-picker-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);backdrop-filter:blur(4px);z-index:500;align-items:center;justify-content:center;">
        <div style="background:#16162a;border:1px solid #2a2a4a;border-radius:12px;padding:1.75rem;width:90%;max-width:480px;position:relative;">
            <button onclick="closeAvatarPicker()" style="position:absolute;top:1rem;right:1rem;background:none;border:none;color:#5a5a7a;font-size:1.2rem;cursor:pointer;">✕</button>
            <div style="font-family:Orbitron,monospace;font-size:.85rem;letter-spacing:2px;color:#00f5ff;margin-bottom:1.25rem;">⚡ CHOOSE YOUR AVATAR</div>
            <div id="avatar-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1.5rem;">' . $grid . '</div>
            <div style="display:flex;gap:.75rem;">
                <button onclick="saveAvatar(\'' . $apiPath . '\')"
                    style="flex:1;background:linear-gradient(135deg,#00f5ff,#00c8d4);color:#000;border:none;border-radius:6px;padding:.65rem;font-family:Orbitron,monospace;font-size:.75rem;font-weight:700;letter-spacing:1.5px;cursor:pointer;">
                    SAVE AVATAR
                </button>
                <button onclick="closeAvatarPicker()"
                    style="background:none;border:1px solid #2a2a4a;border-radius:6px;padding:.65rem 1.2rem;color:#9090b0;font-size:.85rem;cursor:pointer;">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    <script>
    let selectedAvatarKey = "' . $currentKey . '";

    function openAvatarPicker() {
        document.getElementById("avatar-picker-modal").style.display = "flex";
    }
    function closeAvatarPicker() {
        document.getElementById("avatar-picker-modal").style.display = "none";
    }
    function selectAvatar(el) {
        document.querySelectorAll(".avatar-option div:first-child").forEach(d => {
            d.style.border = "2px solid transparent";
            d.style.transform = "scale(1)";
        });
        const box = el.querySelector("div");
        box.style.border = "2px solid #fff";
        box.style.transform = "scale(1.1)";
        selectedAvatarKey = el.dataset.key;
    }
    async function saveAvatar(apiPath) {
        const fd = new FormData();
        fd.append("action", "update_avatar");
        fd.append("avatar_key", selectedAvatarKey);
        const res  = await fetch(apiPath, { method: "POST", body: fd });
        const data = await res.json();
        if (data.success) {
            closeAvatarPicker();
            location.reload();
        } else {
            alert(data.error || "Failed to save avatar.");
        }
    }
    </script>';
}

<?php
// shop.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);

if (!$user) {
    header('Location: /login.php');
    exit;
}

// Получаем все предметы из магазина
$items_query = $db->query("SELECT * FROM game_items ORDER BY price ASC");
$items = [];
while ($row = $items_query->fetchArray(SQLITE3_ASSOC)) {
    $items[] = $row;
}

// Получаем купленные предметы пользователя
$user_items_query = $db->query("
    SELECT ui.*, gi.name, gi.description, gi.icon, gi.price, gi.type 
    FROM user_items ui
    JOIN game_items gi ON ui.item_id = gi.id
    WHERE ui.user_id = {$user['id']}
    ORDER BY ui.purchase_date DESC
");
$user_items = [];
while ($row = $user_items_query->fetchArray(SQLITE3_ASSOC)) {
    $user_items[] = $row;
}

require_once('includes/header.php');
?>

<div class="telegram-shop">
    <div class="shop-header">
        <div class="shop-title">
            <i class="fas fa-gift shop-icon"></i>
            <h1>Магазин ConnectCoin</h1>
        </div>
        <div class="user-balance">
            <i class="fas fa-coins"></i>
            <span><?= getUserBalance($db, $user['id']) ?> CC</span>
        </div>
    </div>

    <div class="shop-tabs">
        <div class="tab active" data-tab="shop">
            <i class="fas fa-store"></i> Магазин
        </div>
        <div class="tab" data-tab="inventory">
            <i class="fas fa-box-open"></i> Мои предметы
        </div>
    </div>

    <div class="tab-content active" id="shop">
        <?php if (!empty($items)): ?>
            <div class="gifts-grid">
                <?php foreach ($items as $item): ?>
                <div class="gift-card <?= $item['type'] === 'premium' ? 'premium' : '' ?>">
                    <?php if ($item['type'] === 'premium'): ?>
                        <div class="premium-badge">PREMIUM</div>
                    <?php endif; ?>
                    
                    <div class="gift-icon">
                        <i class="fas fa-<?= htmlspecialchars($item['icon']) ?>"></i>
                    </div>
                    
                    <div class="gift-info">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p><?= htmlspecialchars($item['description']) ?></p>
                        
                        <div class="gift-footer">
                            <div class="gift-price">
                                <i class="fas fa-coins"></i>
                                <span><?= $item['price'] ?> CC</span>
                            </div>
                            <button class="buy-btn" onclick="buyItem(<?= $item['id'] ?>)">
                                Купить
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open empty-icon"></i>
                <p>В магазине пока нет предметов</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="tab-content" id="inventory">
        <?php if (!empty($user_items)): ?>
            <div class="gifts-grid">
                <?php foreach ($user_items as $item): ?>
                <div class="gift-card inventory <?= $item['is_active'] ? 'active' : '' ?>">
                    <div class="gift-icon">
                        <i class="fas fa-<?= htmlspecialchars($item['icon']) ?>"></i>
                    </div>
                    
                    <div class="gift-info">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="purchase-date">Куплен: <?= date('d.m.Y', strtotime($item['purchase_date'])) ?></p>
                        
                        <div class="gift-actions">
                            <?php if ($item['is_active']): ?>
                                <button class="action-btn remove" onclick="deactivateItem(<?= $item['id'] ?>)">
                                    <i class="fas fa-times"></i> Снять
                                </button>
                            <?php else: ?>
                                <?php if (in_array($item['type'], ['avatar_frame', 'profile_cover', '3rd lavel', '2nd lavel', '1st lavel', 'premium'])): ?>
                                    <button class="action-btn use" onclick="useItem(<?= $item['id'] ?>, '<?= $item['type'] ?>')">
                                        Применить
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button class="action-btn gift" onclick="showGiftDialog(<?= $item['id'] ?>)">
                                Подарить
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-boxes empty-icon"></i>
                <p>У вас пока нет предметов</p>
                <button class="switch-tab" data-tab="shop">Перейти в магазин</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно для подарка -->
<div class="modal" id="giftModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-gift"></i> Подарить предмет</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="giftItemId">
            <div class="form-group">
                <label for="giftUsername">Имя пользователя:</label>
                <input type="text" id="giftUsername" placeholder="Введите имя пользователя">
            </div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn secondary" id="cancelGift">Отмена</button>
            <button class="modal-btn primary" id="sendGift">Отправить</button>
        </div>
    </div>
</div>

<script>
// Переключение табов
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
    });
});

// Переключение на магазин из пустого инвентаря
document.querySelector('.switch-tab')?.addEventListener('click', function() {
    document.querySelector('.tab[data-tab="shop"]').click();
});

// Модальное окно для подарка
function showGiftDialog(itemId) {
    document.getElementById('giftItemId').value = itemId;
    document.getElementById('giftModal').style.display = 'flex';
}

document.querySelector('.close').addEventListener('click', () => {
    document.getElementById('giftModal').style.display = 'none';
});

document.getElementById('cancelGift').addEventListener('click', () => {
    document.getElementById('giftModal').style.display = 'none';
});

document.getElementById('sendGift').addEventListener('click', sendGift);

function buyItem(itemId) {
    if (!confirm('Вы уверены, что хотите купить этот предмет?')) return;
    
    fetch('/actions/buy_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ item_id: itemId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Покупка успешна!');
            location.reload();
        } else {
            alert(data.message || 'Ошибка при покупке');
        }
    });
}

/*function useItem(itemId, itemType) {
    fetch('/actions/use_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            item_id: itemId,
            item_type: itemType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Предмет успешно применён!');
            location.reload();
        } else {
            alert(data.message || 'Ошибка при применении предмета');
        }
    });
}*/


function useItem(itemId, itemType) {
    fetch('/actions/use_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            item_id: itemId,
            item_type: itemType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Оформление успешно применено!');
            location.reload(); // Перезагружаем страницу для применения стиля
        } else {
            alert(data.message || 'Ошибка при применении оформления');
        }
    });
}

function sendGift() {
    const itemId = document.getElementById('giftItemId').value;
    const username = document.getElementById('giftUsername').value.trim();
    
    if (!username) {
        alert('Введите имя пользователя');
        return;
    }
    
    fetch('/actions/gift_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            item_id: itemId,
            username: username
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('giftModal').style.display = 'none';
            alert('Предмет успешно подарен!');
            location.reload();
        } else {
            alert(data.message || 'Ошибка при отправке подарка');
        }
    });
}
function deactivateItem(itemId) {
    if (!confirm('Вы уверены, что хотите снять этот предмет?')) return;
    
    fetch('/actions/deactivate_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ item_id: itemId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Предмет успешно снят!');
            location.reload();
        } else {
            alert(data.message || 'Ошибка при снятии предмета');
        }
    });
}
</script>

<style>
/* Базовые стили */
:root {
    --primary-color: #0088cc;
    --secondary-color: #f0f2f5;
    --text-color: #333;
    --text-light: #666;
    --border-color: #e0e0e0;
    --premium-color: #f0b90b;
    --success-color: #28a745;
    --danger-color: #dc3545;
}

body {
    font-family: 'Segoe UI', Roboto, sans-serif;
    background-color: #f9f9f9;
    color: var(--text-color);
    margin: 0;
    padding: 0;
}

/* Основной контейнер магазина */
.telegram-shop {
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

/* Шапка магазина */
.shop-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.shop-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.shop-title h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.shop-icon {
    color: var(--primary-color);
    font-size: 24px;
}

.user-balance {
    display: flex;
    align-items: center;
    gap: 5px;
    background: var(--secondary-color);
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 500;
}

.user-balance i {
    color: var(--premium-color);
}

/* Табы */
.shop-tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.tab {
    padding: 12px 20px;
    cursor: pointer;
    font-weight: 500;
    color: var(--text-light);
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
}

.tab i {
    margin-right: 8px;
}

.tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

/* Содержимое табов */
.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Сетка подарков */
.gifts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

/* Карточка подарка */
.gift-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--border-color);
    transition: all 0.2s;
}

.gift-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.gift-card.premium {
    border: 1px solid var(--premium-color);
    background: linear-gradient(135deg, #fff9e6 0%, #fff4d6 100%);
}

.gift-card.inventory {
    border-left: 4px solid var(--primary-color);
}

.gift-card.active {
    border: 2px solid var(--success-color);
    background: rgba(40, 167, 69, 0.05);
}

/* Иконка подарка */
.gift-icon {
    padding: 25px 0;
    text-align: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
}

.gift-icon i {
    font-size: 48px;
    color: var(--primary-color);
}

.premium .gift-icon i {
    color: var(--premium-color);
}

/* Информация о подарке */
.gift-info {
    padding: 15px;
}

.gift-info h3 {
    margin: 0 0 8px;
    font-size: 18px;
    font-weight: 600;
}

.gift-info p {
    margin: 0 0 12px;
    color: var(--text-light);
    font-size: 14px;
    line-height: 1.5;
}

.purchase-date {
    font-size: 13px;
    color: #999;
    margin-bottom: 15px !important;
}

/* Футер карточки */
.gift-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
}

.gift-price {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
    color: var(--premium-color);
}

/* Кнопки */
.buy-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.buy-btn:hover {
    background: #0077b3;
}

.gift-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.action-btn {
    flex: 1;
    padding: 8px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn.use {
    background: var(--success-color);
    color: white;
}

.action-btn.use:hover {
    background: #218838;
}

.action-btn.gift {
    background: #ff9500;
    color: white;
}

.action-btn.gift:hover {
    background: #e68a00;
}

/* Бейдж премиум */
.premium-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, var(--premium-color) 0%, #f8d33a 100%);
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-shadow: 0 1px 1px rgba(0,0,0,0.1);
}

/* Состояние "пусто" */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-light);
}

.empty-icon {
    font-size: 60px;
    color: #e0e0e0;
    margin-bottom: 15px;
}

.empty-state p {
    font-size: 16px;
    margin-bottom: 20px;
}

.switch-tab {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.switch-tab:hover {
    background: #0077b3;
}

/* Модальное окно */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 400px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-header .close {
    font-size: 24px;
    cursor: pointer;
    color: var(--text-light);
}

.modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.modal-btn.primary {
    background: var(--primary-color);
    color: white;
}

.modal-btn.primary:hover {
    background: #0077b3;
}

.modal-btn.secondary {
    background: var(--secondary-color);
    color: var(--text-color);
}

.modal-btn.secondary:hover {
    background: #e0e0e0;
}

/* Адаптивность */
@media (max-width: 768px) {
    body{
        margin-bottom: 100px !important;
    }
    .telegram-shop {
        margin: 0;
        border-radius: 0;
        min-height: 100vh;
    }
    
    .gifts-grid {
        grid-template-columns: 1fr;
    }
    
    .shop-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .shop-tabs {
        overflow-x: auto;
        white-space: nowrap;
    }
}
.action-btn.remove {
    background: #dc3545;
    color: white;
}

.action-btn.remove:hover {
    background: #c82333;
}

.action-btn.remove i {
    margin-right: 5px;
}
</style>
<style>
    @media (prefers-color-scheme: light) {
        .mobile-menu-btn{
            color: black;
        }
        .mobile-menu-btn:hover{
            background: #f5f5f5;
        }
        .sidebar-items{
            background: #ffffff !important;
        }
        .sidebar-item{
            background: #ffffff !important;
        }
        .sidebar-item.active{
            background: #e3f2fc !important;
            border-left-color: #0589c6 !important;
            color: #000000 !important;
        }
        .sidebar-item:hover{
            background: #f5f5f5 !important;
            border-left-color: #0589c6 !important;
        }
        .mobile-nav-item.active{
            color: #0589c6;
        }
        .mobile-nav-item:hover{
            background: #f5f5f5;
        }
        .sidebar-badge{
            background: #0589c6;
        }
    }
</style>
<?php require_once('includes/footer.php'); ?>
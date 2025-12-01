<!DOCTYPE html>
<html lang="ru" >
<head>
  <meta charset="UTF-8">
  <title>Шар судьбы 🎱</title>
  <!-- нормализатор CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
  <!-- наши стили -->
  <link rel="stylesheet" href="style.css">

</head>
<style>
    * {
  box-sizing: border-box;
}

/* общие настройки страницы */
body {
  /* располагаем элементы по центру */
  display: flex;
  align-items: center;
  /* настройки шрифта */
  font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
  justify-content: center;
  text-align: center;
  letter-spacing: 1px;
  font-size: 0.8rem;
  /* страница занимает всю высоту окна */
  min-height: 100vh;
  /* фон */
  background: #db0a5b;
}

/* настройки виртуальной формы */
form {
  /* радиус скругления */
  border-radius: 100%;
  /* меняем внешний вид курсора */
  cursor: pointer;
  /* размеры блока */
  height: 300px;
  width: 300px;
  /* используем относительное позиционирование */
  position: relative;
}

/* меняем внешний вид курсора на всех элементах внутри виртуальной формы  */
form * {
  cursor: pointer;
}

/* скрываем все радиокнопки и виртуально выравниваем их по левому краю */
[type='radio'] {
  display: none;
  left: 100%;
  position: absolute;
}

/* настройки виртуальной кнопки сброса */
[type='reset'] {
  /* скрываем её с экрана */
  display: none;
  /* размеры блока сброса */
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  /* делаем кнопку сброса прозрачной */
  opacity: 0;
  /* ставим её поверх остальных элементов */
  z-index: 6;
  /* используем абсолютное позиционирование */
  position: absolute;
}

/* настройки элементов списка */
li {
  /* убираем отступы */
  margin: 0;
  padding: 0;
  /* задаём размеры */
  height: 300px;
  width: 300px;
}

/* настройки подписей к радиокнопкам */
label {
  /* используем блочный элемент */
  display: block;
  /* задаём размеры */
  height: 100%;
  width: 100%;
}

/* настройки таблицы с ответами */
ul {
  /* задаём плавающую анимацию ответов для разных движков браузера */
  -webkit-animation: scale 7s infinite steps(20);
          animation: scale 7s infinite steps(20);
  /* положение и размеры */
  left: 0;
  top: 0;
  width: 100%;
  /* убираем отступы */
  margin: 0;
  padding: 0;
  /* используем абсолютное позиционирование */
  position: absolute;
  /* располагаем их на слое пониже, под кнопкой сброса */
  z-index: 5;
}

/* настройки восьмёрки */
.eight {
  /* радиус скругления */
  border-radius: 100%;
  /* высота и ширина */
  width: 100%;
  height: 100%;
  /* скрываем всё, что выходит за пределы блока */
  overflow: hidden;
  /* используем относительное позиционирование */
  position: relative;
  /* располагаем восьмёрку ещё ниже слоем, под слоем с таблицей */
  z-index: 4;
}

/* фон шара */
.eight__backdrop {
  /* делаем градиентный фон */
  background: radial-gradient(circle at 5% 5%, #666, #222 50%), #222;
  /* радиус скругления */
  border-radius: 100%;
  /* размеры и положение элемента */
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  /* используем абсолютное позиционирование */
  position: absolute;
}

/* настройки числа 8 на шаре */
.eight__number {
  /* выравниваем всё по центру */
  justify-content: center;
  align-items: center;
  /* настройки фона и шрифта */
  background: #fff;
  border-radius: 100%;
  display: flex;
  font-size: 125px;
  /* размеры и положение элемента */
  width: 50%;
  height: 50%;
  top: 50%;
  left: 50%;
  /* используем абсолютное позиционирование */
  position: absolute;
  /* сдвигаем восьмёрку наверх */
  transform: translate(-50%, -50%);
}

/* окно с ответами */
.eight__window {
  /* фон и граница */
  background: radial-gradient(transparent, #000);
  border: 10px double #555;
  /* радиус скругления */
  border-radius: 100%;
  /* высота и положение элемента */
  width: calc(50% + 20px);
  height: calc(50% + 20px);
  left: 50%;
  position: absolute;
  top: 50%;
  /* сдвигаем окно с ответом наверх */
  transform: translate(-50%, -50%);
}

/* размеры лицевой части восьмёрки */
.eight__fascia {
  height: 300px;
  position: relative;
  width: 300px;
}

/* настройки внешнего вида ответов */
span {
  /* цвет фона, текста и тени */
  background: #00f;
  color: #fff;
  text-shadow: 1px 1px 0 #bfbfbf;
  /* используем абсолютное позиционирование */
  position: absolute;
  /* размеры и положение блока */
  top: 50%;
  left: 50%;
  width: 45%;
  height: 45%;
  /* сдвигаем текст */
  transform: translate(-50%, -50%);
  /* переводим текст в верхний регистр */
  text-transform: uppercase;
  /* выравниваем всё по центру */
  display: flex;
  align-items: center;
  justify-content: center;
  /* делаем ответ полностью прозрачным */
  opacity: 0;
  /* настраиваем анимацию колебаний в разных движках */
  -webkit-animation-duration: 10s;
          animation-duration: 10s;
  -webkit-animation-timing-function: linear;
          animation-timing-function: linear;
  -webkit-animation-iteration-count: infinite;
          animation-iteration-count: infinite;
  -webkit-animation-name: floaty;
          animation-name: floaty;
  /* распологаем блок с ответами ниже всех предыдущих */
  z-index: 2;
}


/* треугольник с чётными ответами */
span:nth-of-type(even) {
  /* треугольник направлен вверх */
  clip-path: polygon(0 100%, 50% 20%, 100% 100%);
  -webkit-clip-path: polygon(0 100%, 50% 20%, 100% 100%);
  /* выравниваем по низу треугольника */
  align-items: flex-end;
  /* отступ и размер */
  padding-bottom: 5%;
  top: 40%;
}


/* треугольник с нечётными ответами */
span:nth-of-type(odd) {
  /* треугольник направлен вниз */
  clip-path: polygon(0 0, 50% 80%, 100% 0);
  -webkit-clip-path: polygon(0 0, 50% 80%, 100% 0);
  /* выравниваем по верху треугольника */
  align-items: flex-start;
  /* отступ и размер */
  padding-top: 5%;
  top: 60%;
}


/* если видна восьмёрка и щёлкнули по ней или по шару в целом */
[type='radio']:checked ~ .eight,
[type='radio']:checked ~ .eight__backdrop {
  /* запускаем анимацию встряски шара */
  -webkit-animation: shake 0.25s 4;
          animation: shake 0.25s 4;
}

/* если щёлкнули по ответу */
[type='radio']:checked ~ .eight .eight__fascia {
  /* показываем восьмёрку */
  transform: translate(0, -100%);
  transition: transform 0.25s 1.25s ease;
}

/* если шар встряхнули, показываем выбранный ответ — делаем его видимым */
[type='radio']:checked + span {
  opacity: 1;
  transition: opacity 1s 1.7s;
}

/* если нажали кнопку сброса */
[type='radio']:checked ~ [type='reset'] {
  /* превращаем это в блочный элемент */
  display: block;
}

/* анимация для выбора следующего элемента */
@-webkit-keyframes scale {
  to {
    transform: translateY(-100%);
  }
}
@keyframes scale {
  to {
    transform: translateY(-100%);
  }
}

/* анимация плавающих ответов в разных движках */
@-webkit-keyframes floaty {
  0%, 100% {
    transform: translate(-50%, -50%);
  }
  25% {
    transform: translate(-50%, -50%) translate(-2%, 2%) rotate(2deg);
  }
  50% {
    transform: translate(-50%, -50%) translate(2%, -2%) rotate(-2deg);
  }
  75% {
    transform: translate(-50%, -50%) translate(1%, 1%) rotate(1deg);
  }
}

@keyframes floaty {
  0%, 100% {
    transform: translate(-50%, -50%);
  }
  25% {
    transform: translate(-50%, -50%) translate(-2%, 2%) rotate(2deg);
  }
  50% {
    transform: translate(-50%, -50%) translate(2%, -2%) rotate(-2deg);
  }
  75% {
    transform: translate(-50%, -50%) translate(1%, 1%) rotate(1deg);
  }
}

/* анимация встряски шара в разных движках */
@-webkit-keyframes shake {
  0%, 100% {
    transform: translate(0, 0);
  }
  50% {
    transform: translate(10px, 5px);
  }
  75% {
    transform: translate(-10px, -5px);
  }
}

@keyframes shake {
  0%, 100% {
    transform: translate(0, 0);
  }
  50% {
    transform: translate(10px, 5px);
  }
  75% {
    transform: translate(-10px, -5px);
  }
}
</style>
<body>
<!-- раздел с вариантами ответов -->
<form>
  <!-- ответы -->
  <input type="radio" name="answer" id="Да" value="Да"/><span>Да</span>
  <input type="radio" name="answer" id="Кажется, сработает" value="Кажется, <br> сработает"/><span>Кажется,<br/>сработает</span>
  <input type="radio" name="answer" id="Не в этот раз" value="Не в этот раз"/><span>Не в этот<br>раз</span>
  <input type="radio" name="answer" id="Разумеется" value="Разумеется"/><span>Разумеется</span>
  <input type="radio" name="answer" id="Нет" value="Нет"/><span>Нет</span>
  <input type="radio" name="answer" id="Надо подождать" value="Надо подождать"/><span>Надо<br/>подождать</span>
  <input type="radio" name="answer" id="Скорее всего" value="Скорее всего"/><span>Скорее<br/>всего</span>
  <input type="radio" name="answer" id="Плохая идея" value="Плохая идея"/><span>Плохая<br/>идея</span>
  <input type="radio" name="answer" id="Однозначно" value="Однозначно"/><span>Однозначно</span>
  <input type="radio" name="answer" id="Перепроверь и действуй" value="Перепроверь и действуй"/><span>Перепроверь<br/>и действуй</span>
  <input type="radio" name="answer" id="Встряхни шар ещё раз" value="Встряхни шар ещё раз"/><span>Встряхни <br/> шар<br/> ещё раз</span>
  <input type="radio" name="answer" id="Рискованно" value="Рискованно"/><span>Рискованно</span>
  <input type="radio" name="answer" id="Измени вопрос" value="Измени вопрос"/><span>Измени<br/>вопрос</span>
  <input type="radio" name="answer" id="Действуй" value="Действуй"/><span>Действуй</span>
  <input type="radio" name="answer" id="Всё не так просто" value="Всё не так просто"/><span>Всё<br/> не так<br/>просто</span>
  <!-- шар -->
  <div class="eight__backdrop"></div>
  <!-- раздел с восьмёркой -->
  <div class="eight">
    <!-- лицевая сторона -->
    <div class="eight__fascia">
      <!-- рисуем восьмёрку -->
      <div class="eight__number">8</div>
    </div>
    <!-- обратная сторона -->
    <div class="eight__fascia">
      <!-- окно с ответом -->
      <div class="eight__window"></div>
    </div>
    <!-- таблица c названиями ответов -->
    <ul>
      <li>
        <label for="Да"></label>
      </li>
      <li>
        <label for="Кажется, сработает"></label>
      </li>
      <li>
        <label for="Не в этот раз"></label>
      </li>
      <li>
        <label for="Разумеется"></label>
      </li>
      <li>
        <label for="Нет"></label>
      </li>
      <li>
        <label for="Надо подождать"></label>
      </li>
      <li>
        <label for="Скорее всего"></label>
      </li>
      <li>
        <label for="Плохая идея"></label>
      </li>
      <li>
        <label for="Однозначно"></label>
      </li>
      <li>
        <label for="Перепроверь и действуй"></label>
      </li>
      <li>
        <label for="Встряхни шар ещё раз"></label>
      </li>
      <li>
        <label for="Рискованно"></label>
      </li>
      <li>
        <label for="Измени вопрос"></label>
      </li>
      <li>
        <label for="Действуй"></label>
      </li>
      <li>
        <label for="Всё не так просто"></label>
      </li>
    </ul>
  </div>
  <!-- кнопка сброса -->
  <input type="reset"/>
</form>

</body>
</html>
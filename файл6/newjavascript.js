/* global q */

function getPrices() {
  return {
    prodTypes: [80, 500, 1000],
    prodOptions: {
      option1: 300,
      option2: 400,
      option3: 500
    },
    prodProperties: {
      prop1: 800,
      prop2: 1200
    }
  };
}

function updatePrice() {
  const radios = document.querySelectorAll('input[name="r"]');
  let selectedValue = null;

  for (const radio of radios) {
    if (radio.checked) {
      selectedValue = radio.value;
      break;
    }
  }

  let price = 0;
  const prices = getPrices();

  if (selectedValue) {
    const priceIndex = parseInt(selectedValue) - 1;
    if (priceIndex >= 0 && priceIndex < prices.prodTypes.length) {
      price = prices.prodTypes[priceIndex];
    }
  }
  
  const q = document.getElementById('number1');
let q1 = 1;

if (q && q.value !== '') {
    const pq = parseInt(q.value);

    if (isNaN(pq) || pq <= 0) {
        alert('Количество должно быть положительным числом');
        q.value = '';
        return;
    }

    q1 = pq;
}

  const selectElement = document.getElementById('prodOptions'); 
  const checkboxesDiv = document.getElementById('checkboxes'); 

  if (selectedValue === '1') {
    selectElement.style.display = 'none';
    checkboxesDiv.style.display = 'none';
  } else if (selectedValue === '2') {
    selectElement.style.display = 'block';
    checkboxesDiv.style.display = 'none';
  } else if (selectedValue === '3') {
    selectElement.style.display = 'none';
    checkboxesDiv.style.display = 'block';
  } else {
    selectElement.style.display = 'none';
    checkboxesDiv.style.display = 'none';
  }

  if (selectedValue === '2' && selectElement.value) {
    const optionKey = `option${selectElement.value}`;
    if (prices.prodOptions[optionKey] !== undefined) {
      price += prices.prodOptions[optionKey];
    }
  }

  if (selectedValue === '3') {
    const props = document.querySelectorAll('#checkboxes input[type="checkbox"]');
    for (const prop of props) {
      if (prop.checked) {
        const propKey = prop.name;
        if (prices.prodProperties[propKey] !== undefined) {
          price += prices.prodProperties[propKey];
        }
      }
    }
  }
  
  price *= q1;

  const prodPrice = document.getElementById('prodPrice');
  const result = document.getElementById('result');
  if (prodPrice) {
    result.textContent = `${price} рублей`;
  }
}

window.addEventListener('DOMContentLoaded', function () {
  const radios = document.querySelectorAll('input[name="r"]');
  const selectElement = document.getElementById('prodOptions');
  const checkboxes = document.querySelectorAll('#checkboxes input[type="checkbox"]');
  const q = document.getElementById('number1');

  radios.forEach(radio => radio.addEventListener('change', updatePrice));
  selectElement.addEventListener('change', updatePrice);
  checkboxes.forEach(checkbox => checkbox.addEventListener('change', updatePrice));
  q.addEventListener('input', updatePrice);

  updatePrice();
});

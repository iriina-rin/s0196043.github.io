document.addEventListener('DOMContentLoaded', () => {
    const q = document.getElementById('quantity');
    const p = document.getElementById('product');
    const b = document.getElementById('calculate');
    const r = document.getElementById('result');
    
    const prices = {
        '0': 10000,
        '1': 30000,
        '2': 2500,   
        '3': 2500,   
        '4': 5000    
    };
    
    b.addEventListener('click', () => {
        
        const q1 = parseInt(q.value);
        const p1 = p.value;
        
        const price = prices[p1];
        const r1 = q1 * price;
        
        showResult(q1, p1, r1);
    });
    
    function showResult(q1, p1, r1) {
        const n = p.options[p1].text.split(' ')[0];
        if (q1 >= 0)
        {
            r.innerHTML = `
                <div class="alert alert-success">
                    <strong>Результат расчета:</strong><br>
                    Товар: ${n}<br>
                    Количество: ${q1} шт.<br>
                    Общая стоимость: ${r1.toLocaleString()} ₽
                </div>
            `;
         }
         else
         {
            r.innerHTML = `
                <div class="alert alert-danger">
                Расчёт невозможен. Введите натуральное число
                </div>
            `;
         }
    }
});

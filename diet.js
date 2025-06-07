document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const calendarTitle = document.getElementById('calendarTitle');
    const calendarGrid = document.getElementById('calendarGrid');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    const summaryDate = document.getElementById('summaryDate');
    const totalCalories = document.getElementById('totalCalories');
    const totalProtein = document.getElementById('totalProtein');
    const totalCarbs = document.getElementById('totalCarbs');
    const totalFat = document.getElementById('totalFat');
    const totalFiber = document.getElementById('totalFiber');
    const foodItemsList = document.getElementById('foodItemsList');
    
    // Date state
    let currentDate = new Date();
    let selectedDate = new Date(); // Default to today
    
    // Initialize
    renderCalendar(currentDate);
    loadNutritionData(formatDateForServer(selectedDate));
    
    // Event listeners
    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    });
    
    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    });
    
    // Functions
    function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        
        // Set the calendar title
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                           'July', 'August', 'September', 'October', 'November', 'December'];
        calendarTitle.textContent = `${monthNames[month]} ${year}`;
        
        // Clear existing calendar
        calendarGrid.innerHTML = '';
        
        // Add day headers
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day-header';
            dayHeader.textContent = day;
            calendarGrid.appendChild(dayHeader);
        });
        
        // Get first day of month and last day of month
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        // Get start day (filling in days from previous month)
        const startDay = new Date(firstDay);
        startDay.setDate(startDay.getDate() - firstDay.getDay());
        
        // Fill calendar grid
        for (let i = 0; i < 42; i++) {
            const currentDay = new Date(startDay);
            currentDay.setDate(startDay.getDate() + i);
            
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.textContent = currentDay.getDate();
            
            // Check if day is in current month
            if (currentDay.getMonth() !== month) {
                dayElement.classList.add('other-month');
            }
            
            // Check if day is selected
            if (isSameDay(currentDay, selectedDate)) {
                dayElement.classList.add('active');
            }
            
            // Add click event
            dayElement.addEventListener('click', () => {
                selectedDate = new Date(currentDay);
                
                // Update active state
                document.querySelectorAll('.calendar-day').forEach(day => {
                    day.classList.remove('active');
                });
                dayElement.classList.add('active');
                
                // Update summary date display
                updateSummaryDate(selectedDate);
                
                // Load nutrition data for selected date
                loadNutritionData(formatDateForServer(selectedDate));
            });
            
            calendarGrid.appendChild(dayElement);
            
            // Break after filling in all days of the month plus enough to complete the last week
            if (i > 28 && currentDay.getDate() < 7) {
                break;
            }
        }
        
        // Update summary date display for initial render
        updateSummaryDate(selectedDate);
    }
    
    function updateSummaryDate(date) {
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                           'July', 'August', 'September', 'October', 'November', 'December'];
        
        summaryDate.textContent = `${dayNames[date.getDay()]}, ${monthNames[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
    }
    
    function loadNutritionData(dateStr) {
        // Reset summary values
        totalCalories.textContent = '0';
        totalProtein.textContent = '0';
        totalCarbs.textContent = '0';
        totalFat.textContent = '0';
        totalFiber.textContent = '0';
        
        // Show loading state
        foodItemsList.innerHTML = '<div class="loading">Loading food items...</div>';
        
        // Fetch data from the server
        fetch(`get_consumption.php?date=${dateStr}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayNutritionSummary(data.summary);
                    displayFoodItems(data.food_items);
                } else {
                    foodItemsList.innerHTML = `<div class="no-food-items">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error fetching nutrition data:', error);
                foodItemsList.innerHTML = '<div class="no-food-items">Failed to load food data. Please try again.</div>';
            });
    }
    
    function displayNutritionSummary(summary) {
        if (summary) {
            totalCalories.textContent = Math.round(summary.calories || 0);
            totalProtein.textContent = (summary.protein || 0).toFixed(1);
            totalCarbs.textContent = (summary.carbs || 0).toFixed(1);
            totalFat.textContent = (summary.fat || 0).toFixed(1);
            totalFiber.textContent = (summary.fiber || 0).toFixed(1);
            
            // Update vitamin values
            document.getElementById('totalVitaminA').textContent = (summary.vitamin_a_ug || 0).toFixed(1);
            document.getElementById('totalVitaminE').textContent = (summary.vitamin_e_mg || 0).toFixed(1);
            document.getElementById('totalVitaminD').textContent = ((summary.vitamin_d2_ug || 0) + (summary.vitamin_d3_ug || 0)).toFixed(1);
            document.getElementById('totalVitaminK').textContent = ((summary.vitamin_k1_ug || 0) + (summary.vitamin_k2_ug || 0)).toFixed(1);
            document.getElementById('totalVitaminB1').textContent = (summary.vitamin_b1_mg || 0).toFixed(1);
            document.getElementById('totalVitaminB2').textContent = (summary.vitamin_b2_mg || 0).toFixed(1);
            document.getElementById('totalVitaminB3').textContent = (summary.vitamin_b3_mg || 0).toFixed(1);
            document.getElementById('totalVitaminB5').textContent = (summary.vitamin_b5_mg || 0).toFixed(1);
            document.getElementById('totalVitaminB6').textContent = (summary.vitamin_b6_mg || 0).toFixed(1);
            document.getElementById('totalVitaminB7').textContent = (summary.vitamin_b7_ug || 0).toFixed(1);
            document.getElementById('totalVitaminB9').textContent = (summary.vitamin_b9_ug || 0).toFixed(1);
            document.getElementById('totalVitaminC').textContent = (summary.vitamin_c_mg || 0).toFixed(1);
        }
    }
    
    function displayFoodItems(items) {
        if (!items || items.length === 0) {
            foodItemsList.innerHTML = '<div class="no-food-items">No food items recorded for this date</div>';
            return;
        }
        
        foodItemsList.innerHTML = '';
        items.forEach(item => {
            const foodItem = document.createElement('div');
            foodItem.className = 'food-item';
            
            // Create nutrition badges for vitamins
            const vitaminBadges = [];
            if (item.vitamin_a_ug > 0) vitaminBadges.push(`Vitamin A: ${item.vitamin_a_ug.toFixed(1)}µg`);
            if (item.vitamin_e_mg > 0) vitaminBadges.push(`Vitamin E: ${item.vitamin_e_mg.toFixed(1)}mg`);
            if (item.vitamin_d2_ug > 0 || item.vitamin_d3_ug > 0) {
                const totalD = (item.vitamin_d2_ug || 0) + (item.vitamin_d3_ug || 0);
                vitaminBadges.push(`Vitamin D: ${totalD.toFixed(1)}µg`);
            }
            if (item.vitamin_k1_ug > 0 || item.vitamin_k2_ug > 0) {
                const totalK = (item.vitamin_k1_ug || 0) + (item.vitamin_k2_ug || 0);
                vitaminBadges.push(`Vitamin K: ${totalK.toFixed(1)}µg`);
            }
            if (item.vitamin_b1_mg > 0) vitaminBadges.push(`Vitamin B1: ${item.vitamin_b1_mg.toFixed(1)}mg`);
            if (item.vitamin_b2_mg > 0) vitaminBadges.push(`Vitamin B2: ${item.vitamin_b2_mg.toFixed(1)}mg`);
            if (item.vitamin_b3_mg > 0) vitaminBadges.push(`Vitamin B3: ${item.vitamin_b3_mg.toFixed(1)}mg`);
            if (item.vitamin_b5_mg > 0) vitaminBadges.push(`Vitamin B5: ${item.vitamin_b5_mg.toFixed(1)}mg`);
            if (item.vitamin_b6_mg > 0) vitaminBadges.push(`Vitamin B6: ${item.vitamin_b6_mg.toFixed(1)}mg`);
            if (item.vitamin_b7_ug > 0) vitaminBadges.push(`Vitamin B7: ${item.vitamin_b7_ug.toFixed(1)}µg`);
            if (item.vitamin_b9_ug > 0) vitaminBadges.push(`Vitamin B9: ${item.vitamin_b9_ug.toFixed(1)}µg`);
            if (item.vitamin_c_mg > 0) vitaminBadges.push(`Vitamin C: ${item.vitamin_c_mg.toFixed(1)}mg`);
            
            foodItem.innerHTML = `
                <div class="food-name">${item.food_name}</div>
                <div class="food-detail">
                    <span>${item.servings} ${item.serving_unit}</span>
                    <span>${Math.round(item.calories)} calories</span>
                </div>
                <div class="food-nutrition">
                    <span class="nutrition-badge">Protein: ${(item.protein || 0).toFixed(1)}g</span>
                    <span class="nutrition-badge">Carbs: ${(item.carbs || 0).toFixed(1)}g</span>
                    <span class="nutrition-badge">Fat: ${(item.fat || 0).toFixed(1)}g</span>
                    <span class="nutrition-badge">Fiber: ${(item.fiber || 0).toFixed(1)}g</span>
                    ${vitaminBadges.map(badge => `<span class="nutrition-badge">${badge}</span>`).join('')}
                </div>
            `;
            
            foodItemsList.appendChild(foodItem);
        });
    }
    
    // Helper functions
    function isSameDay(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    }
    
    function formatDateForServer(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
});
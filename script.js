document.addEventListener('DOMContentLoaded', function() {
    const searchBox = document.getElementById('searchBox');
    const resultsContainer = document.getElementById('resultsContainer');
    const servingInputContainer = document.getElementById('servingInputContainer');
    const servingInput = document.getElementById('servingInput');
    const nutritionDetails = document.getElementById('nutritionDetails');
    const saveButton = document.getElementById('saveConsumption');
    
    let searchTimeout;
    let selectedFood = null;

    // Function to handle search
    function performSearch(query) {
        query = query.trim();
        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            servingInputContainer.style.display = 'none';
            nutritionDetails.style.display = 'none';
            return;
        }

        resultsContainer.innerHTML = '<div class="loading">Searching...</div>';
        servingInputContainer.style.display = 'none';
        nutritionDetails.style.display = 'none';

        console.log("Searching for:", query);

        fetch(`search_food.php?search=${encodeURIComponent(query)}`)
            .then(response => {
                console.log("Response status:", response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log("Search results:", data);
                if (data.success) {
                    displayFoodItems(data.data);
                } else {
                    resultsContainer.innerHTML = `<div class="no-results">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                resultsContainer.innerHTML = '<div class="no-results">Error fetching data. Please try again.</div>';
            });
    }

    // Function to display food items (names only)
    function displayFoodItems(foods) {
        if (!foods || foods.length === 0) {
            resultsContainer.innerHTML = '<div class="no-results">No food items found matching your search.</div>';
            return;
        }

        resultsContainer.innerHTML = '';
        foods.forEach(food => {
            const foodItem = document.createElement('div');
            foodItem.className = 'food-item';
            foodItem.innerHTML = `
                <div class="food-name">${food.food_name}</div>
            `;
            
            foodItem.addEventListener('click', () => {
                selectedFood = food;
                servingInputContainer.style.display = 'block';
                servingInput.value = '1'; // Default to 1 serving
                servingInput.focus();
                resultsContainer.style.display = 'none';
                calculateNutrition(1);
            });

            resultsContainer.appendChild(foodItem);
        });
    }

    // Function to calculate and display nutrition based on servings
    function calculateNutrition(servings) {
        if (!selectedFood) return;

        nutritionDetails.style.display = 'block';
        
        // Log the selected food data for debugging
        console.log('Selected food:', selectedFood);
        
        const nutritionData = {
            'energy_kcal': { label: 'Calories', unit: 'kcal' },
            'carb_g': { label: 'Carbohydrates', unit: 'g' },
            'protein_g': { label: 'Protein', unit: 'g' },
            'fat_g': { label: 'Fat', unit: 'g' },
            'fibre_g': { label: 'Fiber', unit: 'g' },
            'unit_serving_vita_ug': { label: 'Vitamin A', unit: 'µg' },
            'unit_serving_vite_mg': { label: 'Vitamin E', unit: 'mg' },
            'unit_serving_vitd2_ug': { label: 'Vitamin D2', unit: 'µg' },
            'unit_serving_vitd3_ug': { label: 'Vitamin D3', unit: 'µg' },
            'unit_serving_vitk1_ug': { label: 'Vitamin K1', unit: 'µg' },
            'unit_serving_vitk2_ug': { label: 'Vitamin K2', unit: 'µg' },
            'unit_serving_vitb1_mg': { label: 'Vitamin B1', unit: 'mg' },
            'unit_serving_vitb2_mg': { label: 'Vitamin B2', unit: 'mg' },
            'unit_serving_vitb3_mg': { label: 'Vitamin B3', unit: 'mg' },
            'unit_serving_vitb5_mg': { label: 'Vitamin B5', unit: 'mg' },
            'unit_serving_vitb6_mg': { label: 'Vitamin B6', unit: 'mg' },
            'unit_serving_vitb7_ug': { label: 'Vitamin B7', unit: 'µg' },
            'unit_serving_vitb9_ug': { label: 'Vitamin B9', unit: 'µg' },
            'unit_serving_vitc_mg': { label: 'Vitamin C', unit: 'mg' }
        };

        let nutritionHtml = '';
        for (const [key, info] of Object.entries(nutritionData)) {
            let value = selectedFood.nutrition[key];
            // Log each nutrition value for debugging
            console.log(`${key}: ${value}`);
            
            if (value === undefined || value === null) continue;
            
            let calculatedValue = parseFloat(value) * parseFloat(servings);
            if (isNaN(calculatedValue)) calculatedValue = 0;
            
            nutritionHtml += `
                <div class="nutrition-item">
                    <div class="nutrition-value">${calculatedValue.toFixed(1)} ${info.unit}</div>
                    <div class="nutrition-label">${info.label}</div>
                </div>
            `;
        }

        nutritionDetails.innerHTML = `
            <div class="food-name">${selectedFood.food_name}</div>
            <div class="food-serving">Serving: ${servings} ${selectedFood.servings_unit}</div>
            <div class="nutrition-grid">
                ${nutritionHtml}
            </div>
            <button id="saveConsumption" class="save-btn">Save This Entry</button>
        `;

        // Re-attach event listener to the save button
        document.getElementById('saveConsumption').addEventListener('click', saveConsumption);
    }

    // Function to save consumption
    function saveConsumption() {
        if (!selectedFood) return;
        
        const servings = parseFloat(servingInput.value) || 1;
        // Validation for servings
        if (isNaN(servings) || servings < 0.1 || servings > 20) {
            alert('Servings must be between 0.1 and 20.');
            return;
        }
        // Validation for nutrients (plausible single-meal limits)
        const n = selectedFood.nutrition;
        const cals = ((n.energy_kcal || 0) * servings);
        const protein = ((n.protein_g || 0) * servings);
        const carbs = ((n.carb_g || 0) * servings);
        const fat = ((n.fat_g || 0) * servings);
        const fiber = ((n.fibre_g || 0) * servings);
        if (cals < 0 || cals > 3000) { alert('Calories per entry must be between 0 and 3000.'); return; }
        if (protein < 0 || protein > 100) { alert('Protein per entry must be between 0 and 100g.'); return; }
        if (carbs < 0 || carbs > 200) { alert('Carbs per entry must be between 0 and 200g.'); return; }
        if (fat < 0 || fat > 100) { alert('Fat per entry must be between 0 and 100g.'); return; }
        if (fiber < 0 || fiber > 30) { alert('Fiber per entry must be between 0 and 30g.'); return; }
        
        // Prepare data to send to the server
        const consumptionData = {
            food_code: selectedFood.food_code,
            food_name: selectedFood.food_name,
            servings: servings,
            serving_unit: selectedFood.servings_unit,
            nutrition: selectedFood.nutrition
        };
        
        console.log("Saving consumption data:", consumptionData);
        
        fetch('save_consumption.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(consumptionData)
        })
        .then(response => response.json())
        .then(result => {
            console.log("Save result:", result);
            if (result.success) {
                alert('Food consumption saved successfully!');
                // Reset the form
                searchBox.value = '';
                servingInput.value = '1';
                nutritionDetails.style.display = 'none';
                servingInputContainer.style.display = 'none';
                resultsContainer.innerHTML = '';
                selectedFood = null;
            } else {
                alert('Error saving: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to save. Please try again.');
        });
    }

    // Event listener for search input
    searchBox.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(this.value);
            resultsContainer.style.display = 'block';
            servingInputContainer.style.display = 'none';
            nutritionDetails.style.display = 'none';
        }, 500);
    });

    // Event listener for serving input
    servingInput.addEventListener('input', function() {
        if (selectedFood) {
            calculateNutrition(this.value || 1);
        }
    });

    // Allow user to go back to search results
    document.addEventListener('click', function(e) {
        if (e.target === searchBox) {
            resultsContainer.style.display = 'block';
            servingInputContainer.style.display = 'none';
            nutritionDetails.style.display = 'none';
        }
    });

    // Initial Save button event listener (if present on page load)
    if (saveButton) {
        saveButton.addEventListener('click', saveConsumption);
    }
});
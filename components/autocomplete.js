
//Klasse zur automatischen Komplettierung der Daten in der index.php (für die Stationen)
class AutocompleteInput {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.data = options.data || [];
        this.onSelect = options.onSelect || ((item) => {});
        this.placeholder = options.placeholder || 'Start typing...';
        
        this.currentFocus = -1;
        this.init();
    }

    init() {
        // Erstellen eines Wrappers
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'autocomplete position-relative';
        this.input.parentNode.insertBefore(this.wrapper, this.input);
        this.wrapper.appendChild(this.input);

        // hinzufügen von Bootstrap klassen und Platzhaltern
        this.input.classList.add('form-control');
        this.input.setAttribute('placeholder', this.placeholder);
        this.input.setAttribute('autocomplete', 'off');

        //fügt Events hinzu
        this.input.addEventListener('input', this.onInput.bind(this));
        this.input.addEventListener('keydown', this.onKeyDown.bind(this));
        document.addEventListener('click', this.closeAllLists.bind(this));
    }

    onInput(e) {
        const val = this.input.value;
        this.closeAllLists();
        
        if (!val) return false;
        this.currentFocus = -1;

        const itemContainer = document.createElement('div');
        itemContainer.setAttribute('class', 'autocomplete-items list-group position-absolute w-100');
        this.wrapper.appendChild(itemContainer);

        this.data.forEach(item => {
            if (item.toLowerCase().includes(val.toLowerCase())) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'list-group-item list-group-item-action';
                
                const matchIndex = item.toLowerCase().indexOf(val.toLowerCase());
                itemDiv.innerHTML = item.substr(0, matchIndex);
                itemDiv.innerHTML += `<strong>${item.substr(matchIndex, val.length)}</strong>`;
                itemDiv.innerHTML += item.substr(matchIndex + val.length);
                
                itemDiv.addEventListener('click', (e) => {
                    this.input.value = item;
                    this.onSelect(item);
                    this.closeAllLists();
                });

                itemContainer.appendChild(itemDiv);
            }
        });
    }

    onKeyDown(e) {
        let items = this.wrapper.querySelector('.autocomplete-items');
        if (items) items = items.getElementsByTagName('div');
        
        if (e.keyCode === 40) { // Runter
            this.currentFocus++;
            this.addActive(items);
        } else if (e.keyCode === 38) { // Hoch
            this.currentFocus--;
            this.addActive(items);
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            if (this.currentFocus > -1 && items) {
                items[this.currentFocus].click();
            }
        }
    }

    addActive(items) {
        if (!items) return;
        this.removeActive(items);
        
        if (this.currentFocus >= items.length) this.currentFocus = 0;
        if (this.currentFocus < 0) this.currentFocus = items.length - 1;
        
        items[this.currentFocus].classList.add('active');
    }

    removeActive(items) {
        Array.from(items).forEach(item => {
            item.classList.remove('active');
        });
    }

    closeAllLists(elmnt) {
        const items = document.getElementsByClassName('autocomplete-items');
        Array.from(items).forEach(item => {
            if (elmnt !== item && elmnt !== this.input) {
                item.parentNode.removeChild(item);
            }
        });
    }

    // Öffentliche Methode um die Daten upzudaten
    updateData(newData) {
        this.data = newData;
    }
}

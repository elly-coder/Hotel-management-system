//In a real application, you would send this data to a server
            fetch('/api/reservations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ date, time, adult, child }),
            })
            .then(response => response.json())
            .then(data => {
                console.log('Success:', data);
                alert('Reservation successful!');
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('There was an error processing your reservation.');
            });





            // Pre-fill the table name (real application, you might have a hidden field for this)
             const tableInput = document.createElement('input');
             tableInput.type = 'hidden';
             tableInput.name = 'table';
             tableInput.value = tableName;
             document.getElementById('reservation-form').appendChild(tableInput);
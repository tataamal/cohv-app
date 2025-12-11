
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize Flatpickr Range
            const fp = flatpickr(".flatpickr-range", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: "{{ request('date') }}"
            });

            // Quick Filter Logic
            window.setQuickDate = function(type) {
                const today = new Date();
                let start, end;

                if (type === 'today') {
                    start = today;
                    end = today;
                } else if (type === 'yesterday') {
                    const yest = new Date(today);
                    yest.setDate(yest.getDate() - 1);
                    start = yest;
                    end = yest;
                }

                // Set to Flatpickr
                fp.setDate([start, end]);
            };
        });
    </script>

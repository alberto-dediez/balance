Feature:
    I want to follow the indicators of my company carbon footprints and compare its properties

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel Uno" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property "Hotel Dos" with id "2222" of account "666" exists
        Given property "2222" has company "C1" assigned

    Scenario: The activity data of a company footprint is prefilled with the sum of its consolidated properties
        Given a completed property carbon footprint of property "Hotel Uno" for year "2025" with total "1000" kg and stays "41000"
        Given a completed property carbon footprint of property "Hotel Dos" for year "2025" with total "500" kg and stays "12000"
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Valores por defecto: suma de las huellas de propiedad del año de las propiedades consolidadas. Revísalos y guarda."
        Then I see a form field "intensity_denominators[stays]" with value "53000"

    Scenario: The comparison between properties lists the properties without data
        Given a completed property carbon footprint of property "Hotel Uno" for year "2025" with total "1000" kg and stays "100"
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with employees answered exists
        Given company carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/kpis/C1?indicator=per_stay"
        Then the response status should be 200
        Then I see a text "Comparación entre propiedades"
        Then I see a text "Hotel Uno"
        Then I see a text "10,00"
        Then I see a text "Sin dato"
        Then I see a text "Sin huella completada"

    Scenario: Without a published segment the sector benchmark is not shown
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with employees answered exists
        Given company carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/results"
        Then the response status should be 200
        When I submit a form with "stays:100" and button "Guardar datos de actividad"
        Then I am redirected to route "company_carbon_footprints_results"
        When I go to "/es/company-carbon-footprints/kpis/C1"
        Then the response status should be 200
        Then I see a text "kg CO₂eq / estancia"
        Then I do not see a text "Referencia sectorial"

    Scenario: With a published segment the sector benchmark is shown
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with employees answered exists
        Given company carbon footprint "999" is completed
        Given a sector benchmark for "company" "2025" "per_stay" with median "3"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/results"
        Then the response status should be 200
        When I submit a form with "stays:100" and button "Guardar datos de actividad"
        Then I am redirected to route "company_carbon_footprints_results"
        When I go to "/es/company-carbon-footprints/kpis/C1"
        Then the response status should be 200
        Then I see a text "Referencia sectorial"
        Then I see a text "mediana 3,00"

Feature:
    I want to make sure that an employee can answer their contribution without logging in

    Scenario: An employee completes the whole contribution wizard
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given employee "Ana" with email "ana@example.com" and id "555" exists
        Given employee contribution with id "222" for event carbon footprint "999" and employee "555" of account "666" with token "token-emp" exists
        When I go to "/es/carbon-footprint/employee-contribution/token-emp/assembly"
        Then the response status should be 200
        Then I see a text "para calcular y agregar la huella de carbono del evento"
        Then I see a text "Montaje"
        When I tick the checkbox "consent_accepted"
        When I tick the checkbox "worked"
        When I fill the unprefixed field "mobilityId" with "M1"
        When I fill the unprefixed field "distanceKm" with "120"
        When I fill the unprefixed field "vehicleOccupants" with "2"
        When I fill the unprefixed field "days" with "3"
        When I fill the unprefixed field "hotelNights" with "2"
        When I submit a form with "" and button "Siguiente"
        Then I am redirected to route "public_carbon_footprint_employee_contribution_celebration"
        Then the response status should be 200
        Then employee contribution "222" should have consent
        Then employee contribution "222" should have step status "assembly_answered"
        Then employee contribution "222" should have "assembly" data with mobility "M1", distance 120, occupants 2, days 3 and hotel nights 2
        When I tick the checkbox "worked"
        When I fill the unprefixed field "mobilityId" with "M1"
        When I fill the unprefixed field "distanceKm" with "45.5"
        When I fill the unprefixed field "vehicleOccupants" with "1"
        When I fill the unprefixed field "days" with "2"
        When I fill the unprefixed field "hotelNights" with "1"
        When I submit a form with "" and button "Siguiente"
        Then I am redirected to route "public_carbon_footprint_employee_contribution_breakdown"
        Then the response status should be 200
        Then employee contribution "222" should have "celebration" data with mobility "M1", distance 45.5, occupants 1, days 2 and hotel nights 1
        When I submit a form with "" and button "Enviar"
        Then I am redirected to route "public_carbon_footprint_employee_contribution_answered"
        Then the response status should be 200
        Then I see a text "¡Gracias por tu contribución!"
        Then employee contribution "222" should have consent
        Then employee contribution "222" should have step status "completed"
        Then employee contribution "222" should have status "answered"

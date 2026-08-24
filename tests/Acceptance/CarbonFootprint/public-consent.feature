Feature:
    I want to make sure that the public surveys do not store anything until the data protection clause is accepted

    Scenario: An attendee survey response is not registered without accepting the clause
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given mobility "Coche" with id "M1" and travel mode "driving" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        When I submit a form with "cameFromAnotherCity:yes|usedHotel:no|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:400|roundTrip:yes|trips:1" and button "Continuar"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/city"
        When I submit a form with "moved:no" and button "Enviar respuesta"
        Then the response status should be 200
        Then I see a text "Debes aceptar el tratamiento de tus datos para continuar."
        Then attendee survey "AS1" should have 0 responses

    Scenario: A trip calculation is not registered without accepting the clause
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given property "777" is located at "40.4680", "-3.6160"
        Given mobility "Coche" with id "M1" and emission factor for the current year exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        When I submit a form with "cameFromAnotherCity:yes|[legs][0][mobilityId]:M1|[legs][0][vehicleOccupants]:1|[legs][0][manualDistanceKm]:120|roundTrip:no|trips:1|overnight:no" and button "Calcular mi huella"
        Then the response status should be 200
        Then I see a text "Debes aceptar el tratamiento de tus datos para continuar."
        Then trip calculation link "TL1" should have 0 trip calculations

    Scenario: The general step of a provider contribution is not stored without accepting the clause
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I go to "/es/carbon-footprint/contribution/token-abc/general"
        When I submit a form with "companyName:New Company Name" and button "Siguiente"
        Then the response status should be 200
        Then I see a text "Debes aceptar el tratamiento de tus datos para continuar."
        Then provider contribution "111" should not have consent
        Then provider contribution "111" should have company name "Provider Company"

    Scenario: The assembly step of an employee contribution is not stored without accepting the clause
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given employee "Ana" with email "ana@example.com" and id "555" exists
        Given employee contribution with id "222" for event carbon footprint "999" and employee "555" of account "666" with token "token-emp" exists
        When I go to "/es/carbon-footprint/employee-contribution/token-emp/assembly"
        Then the response status should be 200
        When I submit a form with "" and button "Siguiente"
        Then the response status should be 200
        Then I see a text "Debes aceptar el tratamiento de tus datos para continuar."
        Then employee contribution "222" should not have consent
        Then employee contribution "222" should have step status "started"

    Scenario: The assembly step of an employee contribution is stored once the clause is accepted
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given employee "Ana" with email "ana@example.com" and id "555" exists
        Given employee contribution with id "222" for event carbon footprint "999" and employee "555" of account "666" with token "token-emp" exists
        When I go to "/es/carbon-footprint/employee-contribution/token-emp/assembly"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "" and button "Siguiente"
        Then I am redirected to route "public_carbon_footprint_employee_contribution_celebration"

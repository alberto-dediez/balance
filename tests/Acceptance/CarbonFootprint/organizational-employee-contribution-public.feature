Feature:
    I want an employee to answer the commuting and business travel survey of an organizational carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given employee "Ana" with email "ana@example.com" and id "E1" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for year "2025" exists
        Given mobility "Avión" with id "M2" and emission factor for year "2025" exists

    Scenario: The link leads to the commuting step
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-701"
        Then I am redirected to route "public_organizational_employee_contribution_commuting"
        Then the response status should be 200
        Then I see a text "Desplazamientos al trabajo"
        Then I see a text "Huella de carbono 2025"
        Then I do not see a form field "workplacePropertyId"

    Scenario: The commuting step is not saved without consent
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-701/commuting"
        When I fill the unprefixed field "weeksPerYear" with "46"
        When I fill the unprefixed field "commutes[0][mobilityId]" with "M1"
        When I fill the unprefixed field "commutes[0][distanceKm]" with "12"
        When I submit a form with "" and button "Siguiente"
        Then the response status should be 200
        Then I see a text "Debes aceptar el tratamiento de tus datos para continuar."
        Then organizational employee contribution "701" should have status "pending"
        Then organizational employee contribution "701" should have 0 commutes and 0 trips

    Scenario: The employee answers both steps
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-701/commuting"
        When I fill the unprefixed field "weeksPerYear" with "46"
        When I fill the unprefixed field "commutes[0][mobilityId]" with "M1"
        When I fill the unprefixed field "commutes[0][distanceKm]" with "12"
        When I fill the unprefixed field "commutes[0][daysPerWeek]" with "5"
        When I fill the unprefixed field "commutes[0][vehicleOccupants]" with "1"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "" and button "Siguiente"
        Then I am redirected to route "public_organizational_employee_contribution_business_travel"
        Then I see a text "Viajes de negocio"
        When I fill the unprefixed field "trips[0][mobilityId]" with "M2"
        When I fill the unprefixed field "trips[0][distanceKm]" with "1200"
        When I fill the unprefixed field "trips[0][trips]" with "1"
        When I fill the unprefixed field "trips[0][hotelNights]" with "2"
        When I submit a form with "" and button "Enviar"
        Then I am redirected to route "public_organizational_employee_contribution_answered"
        Then I see a text "Gracias por tu aportación"
        Then organizational employee contribution "701" should have status "answered"
        Then organizational employee contribution "701" should have 1 commutes and 1 trips
        Then organizational employee contribution "701" should have commute "M1" "12" km "5" days "1" occupants and "46" weeks
        Then organizational employee contribution "701" should have trip "M2" "1200" km "1" trips "1" occupants "2" nights

    Scenario: Invalid survey values are refused with a translated message
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        When I go to "/en/carbon-footprint/organizational-employee-contribution/tok-701/commuting"
        When I fill the unprefixed field "weeksPerYear" with "46"
        When I fill the unprefixed field "commutes[0][mobilityId]" with "M1"
        When I fill the unprefixed field "commutes[0][distanceKm]" with "12"
        When I fill the unprefixed field "commutes[0][daysPerWeek]" with "8"
        When I fill the unprefixed field "commutes[0][vehicleOccupants]" with "1"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "" and button "Next"
        Then I am redirected to route "public_organizational_employee_contribution_commuting"
        Then I see a text "The data could not be saved: Days per week must be between 1 and 7"
        Then organizational employee contribution "701" should have 0 commutes and 0 trips

    Scenario: A business trip above the limits is refused instead of failing
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution "701" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "2" nights
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-701/business-travel"
        When I fill the unprefixed field "trips[0][mobilityId]" with "M2"
        When I fill the unprefixed field "trips[0][distanceKm]" with "1200"
        When I fill the unprefixed field "trips[0][trips]" with "40000"
        When I fill the unprefixed field "trips[0][hotelNights]" with "2"
        When I submit a form with "" and button "Enviar"
        Then I am redirected to route "public_organizational_employee_contribution_business_travel"
        Then I see a text "No se han podido guardar los datos: El número de viajes debe estar entre 1 y 1000"
        Then organizational employee contribution "701" should have trip "M2" "1200" km "1" trips "1" occupants "2" nights

    Scenario: In a company footprint the employee chooses the workplace
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "998" for company "C1" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "702" for "company" carbon footprint "998" and employee "E1" of account "666" with token "tok-702" exists
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-702/commuting"
        Then I see an option "1111" in form field "workplacePropertyId"
        When I fill the unprefixed field "workplacePropertyId" with "1111"
        When I tick the checkbox "noCommuting"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "" and button "Siguiente"
        Then I am redirected to route "public_organizational_employee_contribution_business_travel"
        Then organizational employee contribution "702" should have workplace "1111"
        Then organizational employee contribution "702" should have 0 commutes and 0 trips

    Scenario: An unknown token returns a not found page
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-unknown"
        Then the response status should be 404
        Then I see a text "Este enlace no es válido"

    Scenario: An ignored contribution returns a not found page
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution "701" is ignored
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-701/commuting"
        Then the response status should be 404

    Scenario: Answering a contribution of a completed footprint is refused
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given property carbon footprint "999" is completed
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-701/commuting"
        When I tick the checkbox "noCommuting"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "" and button "Siguiente"
        Then the response status should be 409
        Then I see a text "Esta huella de carbono ya se ha cerrado"

    Scenario: Answering the business travel of a completed footprint is refused
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution "701" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "2" nights
        Given property carbon footprint "999" is completed
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-701/business-travel"
        When I tick the checkbox "noTrips"
        When I submit a form with "" and button "Enviar"
        Then the response status should be 409
        Then I see a text "Esta huella de carbono ya se ha cerrado"

Feature:
    I want to go through the employees step of a company carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given employee "Ana" with email "ana@example.com" and id "E1" of account "666" exists
        Given employee "Luis" with email "luis@example.com" and id "E2" of account "666" exists

    Scenario: Next in the providers step leads to the employees step
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 3 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/providers"
        When I submit the providers step form
        Then I am redirected to route "company_carbon_footprints_employees"
        Then the response status should be 200
        Then the wizard should show the step "Empleados"

    Scenario: The employees step redirects to the providers step when it has not been answered
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 3 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/employees"
        Then I am redirected to route "company_carbon_footprints_providers"

    Scenario: The employees step is skipped when the whole scope 3 is excluded
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 3 excluded exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/employees"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then the wizard should not show the step "Empleados"
        Then company carbon footprint "999" should have status "employees_answered"

    Scenario: The employees step leads back to scope 3 when the whole scope 3 is excluded and the step is not answered
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered and later excluded exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/employees"
        Then I am redirected to route "company_carbon_footprints_scope_3"
        Then company carbon footprint "999" should have status "providers_answered"

    Scenario: Saving the employees step invites the selected employees and leads to the mitigation plan
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/employees"
        When I submit the employees step form with employees "E1,E2" and total "40"
        Then an email with subject "Aporta tus desplazamientos y viajes de 2025 para Hotelera" was sent to "ana@example.com"
        Then an email with subject "Aporta tus desplazamientos y viajes de 2025 para Hotelera" was sent to "luis@example.com"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then company carbon footprint "999" should have status "employees_answered"
        Then company carbon footprint "999" should have 2 organizational employee contributions
        Then company carbon footprint "999" should have 2 organizational employee contributions with status "pending"

    Scenario: A total below the selected employees is refused
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/employees"
        When I submit the employees step form with employees "E1,E2" and total "1"
        Then I am redirected to route "company_carbon_footprints_employees"
        Then I see a text "No se han podido asignar los empleados: El número total de empleados no puede ser inferior al número de empleados seleccionados"
        Then company carbon footprint "999" should have status "providers_answered"
        Then company carbon footprint "999" should have 0 organizational employee contributions

    Scenario: Unselecting an employee ignores the contribution and its link stops working
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "company" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/employees"
        When I submit the employees step form with employees "E2" and total "40"
        Then I am redirected to route "company_carbon_footprints_mitigation_plan"
        Then organizational employee contribution "701" should have status "ignored"
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-701"
        Then the response status should be 404

    Scenario: The contributions show the workplace of each employee
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "company" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution "701" has workplace "1111"
        Given organizational employee contribution with id "702" for "company" carbon footprint "999" and employee "E2" of account "666" with token "tok-702" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/employees"
        Then the response status should be 200
        Then I see a text "Centro de trabajo"
        Then I see a text "Hotel"
        Then I see a text "Sin asignar a propiedad"

    Scenario: The mitigation plan redirects to the employees step until it is answered
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "company_carbon_footprints_employees"
        When I go to "/es/company-carbon-footprints/999/step/results"
        Then I am redirected to route "company_carbon_footprints_employees"
        When I go to "/es/company-carbon-footprints/999/edit"
        Then I am redirected to route "company_carbon_footprints_employees"
        When I make a POST request to "/es/company-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "company_carbon_footprints_employees"
        Then company carbon footprint "999" should not be completed

    Scenario: A user with the User role cannot access the employees step
        Given user "user@dediez.software" with role "ROLE_USER" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        When I am logged as "user@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/employees"
        Then the response status should be 403

    Scenario: The owner consults, resends, ignores and reactivates a contribution
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        Given mobility "Coche" with id "M1" and emission factor for year "2025" exists
        Given mobility "Avión" with id "M2" and emission factor for year "2025" exists
        Given organizational employee contribution with id "701" for "company" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution "701" has workplace "1111"
        Given organizational employee contribution "701" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "2" nights
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/employees/701"
        Then the response status should be 200
        Then I see a text "Desplazamientos al trabajo"
        Then I see a text "Coche"
        Then I see a text "Avión"
        Then I see a text "Hotel"
        When I go to "/es/company-carbon-footprints/999/step/employees"
        When I click on the action "Reenviar"
        Then I am redirected to route "company_carbon_footprints_employees"
        Then I see a text "Se ha reenviado la encuesta a Employee E1"
        When I click on the action "Descartar"
        Then I am redirected to route "company_carbon_footprints_employees"
        Then I see a text "Se ha descartado la aportación de Employee E1"
        Then organizational employee contribution "701" should have status "ignored"
        When I click on the action "Reactivar"
        Then I am redirected to route "company_carbon_footprints_employees"
        Then organizational employee contribution "701" should have status "answered"

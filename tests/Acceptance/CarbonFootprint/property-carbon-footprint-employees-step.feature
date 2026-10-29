Feature:
    I want to go through the employees step of a property carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given employee "Ana" with email "ana@example.com" and id "E1" of account "666" exists
        Given employee "Luis" with email "luis@example.com" and id "E2" of account "666" exists

    Scenario: Next in the providers step leads to the employees step
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/providers"
        When I submit the providers step form
        Then I am redirected to route "property_carbon_footprints_employees"
        Then the response status should be 200
        Then I see a text "Contribuciones"
        Then I see a text "Ana"
        Then the wizard should show the step "Empleados"
        Then property carbon footprint "999" should have status "providers_answered"

    Scenario: The employees step redirects to the providers step when it has not been answered
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        Then I am redirected to route "property_carbon_footprints_providers"

    Scenario: The employees step is skipped when the whole scope 3 is excluded
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 excluded exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then the wizard should not show the step "Empleados"
        Then property carbon footprint "999" should have status "employees_answered"

    Scenario: The employees step leads back to scope 3 when the whole scope 3 is excluded and the step is not answered
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered and later excluded exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        Then property carbon footprint "999" should have status "providers_answered"

    Scenario: Saving the employees step invites the selected employees and leads to the mitigation plan
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        When I submit the employees step form with employees "E1,E2" and total "40"
        Then an email with subject "Aporta tus desplazamientos y viajes de 2025 para Hotel" was sent to "ana@example.com"
        Then an email with subject "Aporta tus desplazamientos y viajes de 2025 para Hotel" was sent to "luis@example.com"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then property carbon footprint "999" should have status "employees_answered"
        Then property carbon footprint "999" should have 2 organizational employee contributions
        Then property carbon footprint "999" should have 2 organizational employee contributions with status "pending"

    Scenario: A total below the selected employees is refused
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        When I submit the employees step form with employees "E1,E2" and total "1"
        Then I am redirected to route "property_carbon_footprints_employees"
        Then I see a text "No se han podido asignar los empleados: El número total de empleados no puede ser inferior al número de empleados seleccionados"
        Then property carbon footprint "999" should have status "providers_answered"
        Then property carbon footprint "999" should have 0 organizational employee contributions

    Scenario: Unselecting an employee ignores the contribution and its link stops working
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        When I submit the employees step form with employees "E2" and total "40"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then organizational employee contribution "701" should have status "ignored"
        When I go to "/es/carbon-footprint/organizational-employee-contribution/tok-701"
        Then the response status should be 404

    Scenario: The mitigation plan and the results redirect to the employees step until it is answered
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "property_carbon_footprints_employees"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then I am redirected to route "property_carbon_footprints_employees"
        When I go to "/es/property-carbon-footprints/999/edit"
        Then I am redirected to route "property_carbon_footprints_employees"

    Scenario: The footprint cannot be completed until the employees step is answered
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I make a POST request to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "property_carbon_footprints_employees"
        Then property carbon footprint "999" should not be completed

    Scenario: A user with the User role cannot access the employees step
        Given user "user@dediez.software" with role "ROLE_USER" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        When I am logged as "user@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        Then the response status should be 403

    Scenario: The owner consults, resends, ignores and reactivates a contribution
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given mobility "Coche" with id "M1" and emission factor for year "2025" exists
        Given mobility "Avión" with id "M2" and emission factor for year "2025" exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution "701" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "2" nights
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/employees/701"
        Then the response status should be 200
        Then I see a text "Desplazamientos al trabajo"
        Then I see a text "Coche"
        Then I see a text "Avión"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        When I click on the action "Reenviar"
        Then I am redirected to route "property_carbon_footprints_employees"
        Then I see a text "Se ha reenviado la encuesta a Employee E1"
        When I click on the action "Descartar"
        Then I am redirected to route "property_carbon_footprints_employees"
        Then I see a text "Se ha descartado la aportación de Employee E1"
        Then organizational employee contribution "701" should have status "ignored"
        When I click on the action "Reactivar"
        Then I am redirected to route "property_carbon_footprints_employees"
        Then organizational employee contribution "701" should have status "answered"

    Scenario: The detail of a pending contribution says it has not been answered yet
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/employees/701"
        Then the response status should be 200
        Then I see a text "Pendiente de respuesta."
        Then I do not see a text "No se desplaza al centro de trabajo."
        Then I do not see a text "Sin viajes de negocio."
        When I go to "/es/property-carbon-footprints/999/step/employees"
        Then I see a text "Pendiente"

    Scenario: The register employee link is shown only while the footprint is editable
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given property "Hostal" with id "2222" of account "777" exists
        Given property carbon footprint with id "998" for property "2222" of account "777" and year "2025" with employees answered exists
        When I am logged as "other@dediez.software"
        When I go to "/es/property-carbon-footprints/998/step/employees"
        Then I see a text "No hay empleados registrados para esta cuenta."
        Then I see a link with name "Registrar empleado"
        Given property carbon footprint "998" is completed
        When I go to "/es/property-carbon-footprints/998/step/employees"
        Then I see a text "No hay empleados registrados para esta cuenta."
        Then I do not see a link with name "Registrar empleado"

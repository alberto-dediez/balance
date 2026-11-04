Feature:
    I want a materiality step between scope 2 and scope 3 in the carbon footprint wizard of a property and a company

    Background:
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "777" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given company "Acme" with id "2222" of account "666" exists
        Given standard materiality masters are seeded

    Scenario: The scope 3 step redirects to the materiality step while materiality has not been answered
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        Then I am redirected to route "property_carbon_footprints_materiality"

    Scenario: The edit action of a footprint with scope 2 answered lands on the materiality step
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/edit"
        Then I am redirected to route "property_carbon_footprints_materiality"

    Scenario: The materiality step is shown in third position of the wizard
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then the response status should be 200
        Then I see a text "Alcance 2" before a text "Materialidad"
        Then I see a text "Materialidad" before a text "Alcance 3"
        Then I see a text "Evaluación de materialidad"
        Then I see a button with name "start_materiality_assessment[start]"
        Then I see a text "Continuar sin evaluar"

    Scenario: The step shows the questionnaire grouped by direction
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then the response status should be 200
        Then I see a text "Cuestionario"
        Then I see a text "Aguas arriba" before a text "Aguas abajo"

    Scenario: Skipping the materiality step answers it and moves to scope 3
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        When I skip the materiality step
        Then I am redirected to route "property_carbon_footprints_scope_3"
        Then property carbon footprint "999" should have status "materiality_answered"
        Then property carbon footprint "999" should have materiality answered
        Then materiality assessment for "property" carbon footprint "999" should not exist

    Scenario: The materiality step of a completed footprint is read only
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then the response status should be 200
        Then I do not see a button with name "start_materiality_assessment[start]"
        Then I do not see a text "Continuar sin evaluar"
        Then I see a text "La huella se completó sin evaluación de materialidad."

    Scenario: An owner of another account cannot access the materiality step
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "other@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then the response status should be 403

    Scenario: An admin without impersonating cannot access the materiality step
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then the response status should be 403

    Scenario: An admin impersonating the account accesses the materiality step
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "MyCompany"
        When I go to "/es/property-carbon-footprints/999/step/materiality"
        Then the response status should be 200

    Scenario: The company wizard also shows the materiality step and allows skipping it
        Given company carbon footprint with id "888" for company "2222" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/888/step/scope-3"
        Then I am redirected to route "company_carbon_footprints_materiality"
        Then I see a text "Evaluación de materialidad"
        When I skip the materiality step
        Then I am redirected to route "company_carbon_footprints_scope_3"
        Then company carbon footprint "888" should have status "materiality_answered"

    Scenario: An owner of another account cannot access the materiality step of a company
        Given company carbon footprint with id "888" for company "2222" of account "666" and year "2025" with scope 2 answered exists
        When I am logged as "other@dediez.software"
        When I go to "/es/company-carbon-footprints/888/step/materiality"
        Then the response status should be 403

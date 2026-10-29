Feature:
    I want to define the reduction and mitigation measures of a property carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists

    Scenario: Saving scopes 2 and 3 leads to the mitigation plan step without completing the footprint
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then I see a text "Medidas de Reducción y Mitigación"
        Then I see a text "No hay medidas en el plan"
        Then property carbon footprint "999" should not be completed

    Scenario: Saving scope 3 for the first time imports the measures of the previous year
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with scope 2 answered exists
        Given mitigation action "Caldera de condensación" with id "A1" for property carbon footprint "998" of account "666" with target year "2030" exists
        Given mitigation action "Medida terminada" with id "A2" for property carbon footprint "998" of account "666" with target year "2025" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then property carbon footprint "999" should have 1 mitigation actions
        Then I see a text "Caldera de condensación"
        Then I do not see a text "Medida terminada"

    Scenario: Saving scope 3 with the mitigation plan already started does not import anything
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given property carbon footprint "999" has its mitigation plan started
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with scope 2 answered exists
        Given mitigation action "Caldera de condensación" with id "A1" for property carbon footprint "998" of account "666" with target year "2030" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then property carbon footprint "999" should have 0 mitigation actions

    Scenario: Saving scope 3 for the first time keeps the measures already created
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with scope 2 answered exists
        Given mitigation action "Caldera de condensación" with id "A1" for property carbon footprint "998" of account "666" with target year "2030" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given mitigation action "Medida propia" with id "A3" for property carbon footprint "999" of account "666" with target year "2030" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then property carbon footprint "999" should have 1 mitigation actions
        Then I see a text "Medida propia"

    Scenario: The mitigation plan step redirects to scope 2 when scope 2 has not been answered
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "property_carbon_footprints_scope_2"

    Scenario: An owner registers a manual measure
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        When I click on link "Añadir medida al plan"
        Then the response status should be 200
        When I submit a form with "name:Paneles solares|targetYear:2030" and button "Registrar"
        Then I am redirected to route "mitigation_actions_edit"
        Then the only mitigation action of property carbon footprint "999" should be named "Paneles solares" in "es"

    Scenario: A manual measure requires a name
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/mitigation-actions/property/999/register"
        When I submit a form with "name:|targetYear:2030" and button "Registrar"
        Then the response status should be 200
        Then I see a text "El nombre es obligatorio si no se escoge una medida sugerida"
        Then property carbon footprint "999" should have 0 mitigation actions

    Scenario: A measure registered from a suggested one generates its effects and copies its translations
        Given combustible "Gas natural" with id "CB1" and emission factor for year "2025" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" has a combustible "CB1" activity of "1000" kg
        Given mitigation measure category "Energía" with id "C1" exists
        Given mitigation measure "Caldera eficiente" with id "M1" of category "C1" reducing source "fixed_facilities" between "0.1" and "0.3" exists
        Given mitigation measure "M1" is translated to "en" as "Efficient boiler"
        When I am logged as "owner@dediez.software"
        When I go to "/es/mitigation-actions/property/999/register"
        When I submit a form with "measure:M1|targetYear:2030" and button "Registrar"
        Then I am redirected to route "mitigation_actions_edit"
        Then the only mitigation action of property carbon footprint "999" should reduce source "fixed_facilities" item "CB1" by "0.1"
        Then the only mitigation action of property carbon footprint "999" should be named "Caldera eficiente" in "es"
        Then the only mitigation action of property carbon footprint "999" should be named "Efficient boiler" in "en"

    Scenario: A measure registered from a suggested one can reduce a scope 3 source
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" has a purchase service activity "PS1" in scope 3
        Given mitigation measure category "Compras" with id "C1" exists
        Given mitigation measure "Compra responsable" with id "M1" of category "C1" reducing source "purchased_goods_and_services" between "0.1" and "0.3" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/mitigation-actions/property/999/register"
        When I submit a form with "measure:M1|targetYear:2030" and button "Registrar"
        Then I am redirected to route "mitigation_actions_edit"
        Then the only mitigation action of property carbon footprint "999" should reduce source "purchased_goods_and_services" item "PS1" by "0.1"

    Scenario: Editing a measure in English stores the English translation
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given mitigation action "Paneles solares" with id "A1" for property carbon footprint "999" of account "666" with target year "2030" exists
        When I am logged as "owner@dediez.software"
        When I go to "/en/mitigation-actions/A1/edit"
        Then the response status should be 200
        When I submit a form with "name:Solar panels" and button "Save"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then mitigation action "A1" should be named "Solar panels" in "en"
        Then mitigation action "A1" should be named "Paneles solares" in "es"

    Scenario: A reduction out of the range of the effect is rejected
        Given combustible "Gas natural" with id "CB1" and emission factor for year "2025" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" has a combustible "CB1" activity of "1000" kg
        Given mitigation measure category "Energía" with id "C1" exists
        Given mitigation measure "Caldera eficiente" with id "M1" of category "C1" reducing source "fixed_facilities" between "0.1" and "0.3" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/mitigation-actions/property/999/register"
        When I submit a form with "measure:M1|targetYear:2030" and button "Registrar"
        Then I am redirected to route "mitigation_actions_edit"
        When I submit a form with "[effects][0][reduction]:50" and button "Guardar"
        Then the response status should be 200
        Then I see a text "El valor de reducción de esta medida debe estar dentro del rango establecido"
        Then the only mitigation action of property carbon footprint "999" should reduce source "fixed_facilities" item "CB1" by "0.1"

    Scenario: A target year that is not after the base year is rejected
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given mitigation action "Paneles solares" with id "A1" for property carbon footprint "999" of account "666" with target year "2030" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/mitigation-actions/A1/edit"
        When I submit a form with "targetYear:2025" and button "Guardar"
        Then the response status should be 200
        Then I see a text "Este valor debería ser mayor que 2025"

    Scenario: An owner removes a measure
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given mitigation action "Paneles solares" with id "A1" for property carbon footprint "999" of account "666" with target year "2030" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/mitigation-actions/A1/remove" with csrf token "remove"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then I see a text "Se ha eliminado la medida Paneles solares"
        Then property carbon footprint "999" should have 0 mitigation actions

    Scenario: Importing the previous measures replaces the current ones
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with scope 2 answered exists
        Given mitigation action "Caldera de condensación" with id "A1" for property carbon footprint "998" of account "666" with target year "2030" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given mitigation action "Medida propia" with id "A3" for property carbon footprint "999" of account "666" with target year "2030" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        When I click on link "Importar acciones previas"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then I see a text "Se han importado 1 medidas."
        Then I see a text "Caldera de condensación"
        Then I do not see a text "Medida propia"
        Then property carbon footprint "999" should have 1 mitigation actions

    Scenario: Without a previous footprint the import is not offered
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then the response status should be 200
        Then I do not see a text "Importar acciones previas"

    Scenario: The projection of the plan is shown in the step
        Given combustible "Gas natural" with id "CB1" and emission factor for year "2025" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" has a combustible "CB1" activity of "1000" kg
        Given mitigation action "Caldera de condensación" with id "A1" for property carbon footprint "999" of account "666" with target year "2030" exists
        Given mitigation action "A1" reduces source "fixed_facilities" item "CB1" by "0.2"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then the response status should be 200
        Then I see a text "Emisiones esperadas por año en kg CO₂ eq"
        Then I see a text "Reducción interanual %"
        Then I see a text "800,00"

    Scenario: Next completes the footprint and registers its report
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "property_carbon_footprints_results"
        Then property carbon footprint "999" should be completed
        Then the report of property carbon footprint "999" should be "generating_texts"

    Scenario: The step of a completed footprint is read only
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with scope 2 answered exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given mitigation action "Paneles solares" with id "A1" for property carbon footprint "999" of account "666" with target year "2030" exists
        Given property carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then the response status should be 200
        Then I see a text "Paneles solares"
        Then I do not see a text "Siguiente"
        Then I do not see a text "Añadir medida al plan"
        Then I do not see a text "Importar acciones previas"

    Scenario: Posting the step of a completed footprint redirects with a warning
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I make a POST request to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "La huella está completada. Reábrela para modificarla."

    Scenario: An owner of another account cannot access the measures
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given mitigation action "Paneles solares" with id "A1" for property carbon footprint "999" of account "666" with target year "2030" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then the response status should be 403
        When I go to "/es/mitigation-actions/A1/edit"
        Then the response status should be 403
        When I go to "/es/mitigation-actions/property/999/register"
        Then the response status should be 403

    Scenario: A user with the User role cannot access the measures
        Given user "user@dediez.software" with role "ROLE_USER" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given mitigation action "Paneles solares" with id "A1" for property carbon footprint "999" of account "666" with target year "2030" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then the response status should be 403
        When I go to "/es/mitigation-actions/A1/edit"
        Then the response status should be 403

    Scenario: Previous in the mitigation plan step leads to the employees step
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then the response status should be 200
        Then the previous step link should point to "/es/property-carbon-footprints/999/step/employees"

    Scenario: Previous in the mitigation plan step leads to scope 3 when scope 3 is excluded
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 excluded exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        Then the response status should be 200
        Then the previous step link should point to "/es/property-carbon-footprints/999/step/scope-3"

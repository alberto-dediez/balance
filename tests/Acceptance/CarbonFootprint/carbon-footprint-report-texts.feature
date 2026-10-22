Feature:
    I want the owner to review the report texts when the account allows it

    Scenario: The owner sees the texts screen and saves a draft
        Given account "MyCompany" with id "666" exists
        Given account "666" has report permissions can edit texts "1" and auto review "1"
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "draft" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Los textos del informe están listos para revisar."
        Then I see a text "Revisar textos del informe"
        When I go to "/es/carbon-footprint-reports/999/texts"
        Then the response status should be 200
        Then I see a text "Propósito"
        Then I see a text "Emitir informe"
        When I submit a form with "purpose:Propósito corregido" and button "Guardar borrador"
        Then I am redirected to route "carbon_footprint_reports_texts"
        Then I see a text "Textos guardados."
        Then the report of property carbon footprint "999" should be "draft"
        Then the report of property carbon footprint "999" should have section "purpose" with "Propósito corregido"

    Scenario: The owner issues the report directly when the account has auto review
        Given account "MyCompany" with id "666" exists
        Given account "666" has report permissions can edit texts "1" and auto review "1"
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "draft" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint-reports/999/texts"
        When I submit a form with "" and button "Emitir informe"
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "Se está generando el informe, te notificaremos cuando esté disponible."
        Then the report of property carbon footprint "999" should be "generating_pdf"

    Scenario: The owner submits the report for review when the account has no auto review
        Given account "MyCompany" with id "666" exists
        Given account "666" has report permissions can edit texts "1" and auto review "0"
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "draft" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint-reports/999/texts"
        Then the response status should be 200
        Then I do not see a text "Emitir informe"
        When I submit a form with "purpose:Propósito revisado" and button "Solicitar revisión"
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "Informe enviado a revisión."
        Then the report of property carbon footprint "999" should be "pending_review"
        Then the report of property carbon footprint "999" should have section "purpose" with "Propósito revisado"

    Scenario: The texts screen is not available when the account cannot edit texts
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "draft" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint-reports/999/texts"
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "El informe no tiene textos pendientes de revisar."

    Scenario: An owner of another account cannot open the texts screen
        Given account "MyCompany" with id "666" exists
        Given account "666" has report permissions can edit texts "1" and auto review "1"
        Given account "Other" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "draft" requested by "owner@dediez.software"
        When I am logged as "other@dediez.software"
        When I go to "/es/carbon-footprint-reports/999/texts"
        Then the response status should be 403

    Scenario: The texts screen redirects when the report is already pending review
        Given account "MyCompany" with id "666" exists
        Given account "666" has report permissions can edit texts "1" and auto review "0"
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint-reports/999/texts"
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "El informe no tiene textos pendientes de revisar."

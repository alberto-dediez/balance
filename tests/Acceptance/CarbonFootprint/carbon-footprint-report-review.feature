Feature:
    I want an admin to review the pending carbon footprint reports

    Scenario: The admin lists the pending reports and opens the review screen
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "admin"
        When I go to "/es/admin/carbon-footprint-reports"
        Then the response status should be 200
        Then I see a text "Informes de huella"
        Then I see a text "Hotel"
        Then I see a text "MyCompany"
        Then I see a text "Pendiente de revisión"
        When I go to "/es/admin/carbon-footprint-reports/999/review"
        Then the response status should be 200
        Then I see a text "Hotel"
        Then I see a text "Propósito"
        Then I see a text "Aprobar y emitir"
        Then I see a text "Rechazar"

    Scenario: The admin approves the report
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "admin"
        When I go to "/es/admin/carbon-footprint-reports/999/review"
        When I submit a form with "purpose:Propósito aprobado" and button "Aprobar y emitir"
        Then I am redirected to route "admin_carbon_footprint_reports"
        Then I see a text "Informe aprobado. Se está generando el PDF."
        Then the report of property carbon footprint "999" should be "generating_pdf"
        Then the report of property carbon footprint "999" should have section "purpose" with "Propósito aprobado"

    Scenario: Rejecting requires a comment
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "admin"
        When I go to "/es/admin/carbon-footprint-reports/999/review"
        When I submit a form with "comment:" and button "Rechazar"
        Then the response status should be 200
        Then I see a text "Indica el motivo del rechazo."
        Then the report of property carbon footprint "999" should be "pending_review"

    Scenario: Rejecting the report reopens the footprint
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then property carbon footprint "999" should be completed
        Given property carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "admin"
        When I go to "/es/admin/carbon-footprint-reports/999/review"
        When I submit a form with "comment:Revisa el alcance 2" and button "Rechazar"
        Then I am redirected to route "admin_carbon_footprint_reports"
        Then I see a text "Informe rechazado. La huella se ha reabierto."
        Then the report of property carbon footprint "999" should be "rejected"
        Then property carbon footprint "999" should not be completed

    Scenario: An owner cannot access the admin review
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/carbon-footprint-reports"
        Then the response status should be 403
        When I go to "/es/admin/carbon-footprint-reports/999/review"
        Then the response status should be 403

    Scenario: The admin filters the list by subject type and status
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "admin"
        When I go to "/es/admin/carbon-footprint-reports"
        When I submit a form with "status:generated|subjectType:property" and button "list_carbon_footprint_reports[filter]"
        Then the response status should be 200
        Then I see a text "Hotel"
        Then I see a text "Emitido"
        Then I see a text "Propiedad"

    Scenario: Reviewing an already generated report redirects to the list with a warning
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "admin"
        When I go to "/es/admin/carbon-footprint-reports/999/review"
        Then I am redirected to route "admin_carbon_footprint_reports"
        Then I see a text "Este informe ya no está pendiente de revisión."

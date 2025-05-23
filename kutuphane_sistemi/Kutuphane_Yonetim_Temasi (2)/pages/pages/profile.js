// import node module libraries
import { Col, Row, Container } from 'react-bootstrap';

// import widget as custom components
import { PageHeading } from 'widgets'

// import sub components
import {
  AboutMe,
  ActivityFeed,
  MyTeam,
  ProfilHeader,
  ProjectsContributions,
  RecentFromBlog
} from 'sub-components'

const Profil = () => {
  return (
    <Container fluid className="p-6">
      {/* Page Heading */}
      <PageHeading heading="Overview"/>

      {/* Profil Header  */}
      <ProfilHeader />

      {/* content */}
      <div className="py-6">
        <Row>

          {/* About Me */}
          <AboutMe />

          {/* Projects Contributions */}
          <ProjectsContributions />

          {/* Recent From Blog */}
          <RecentFromBlog />

          <Col xl={6} lg={12} md={12} xs={12} className="mb-6">

            {/* My Team */}
            <MyTeam />

            {/* Activity Feed */}
            <ActivityFeed />

          </Col>
        </Row>
      </div>

    </Container>
  )
}

export default Profil
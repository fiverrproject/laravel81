/**
 * Optional: call before POST /api/resume/create from browser or Postman pre-request.
 *
 *   const payload = mapResumePayloadForTemplates(yourJson);
 */
function mapResumePayloadForTemplates(body) {
  const personal = body['Personal Details'] || body.personal_details || {};
  const mapSkills = (items) => {
    if (!Array.isArray(items)) return items || '';
    const out = items.map((row) => {
      const name = row.skillName || row.name || row.skill || '';
      let exp = row.expertise;
      if (exp === '' || exp == null) exp = 0;
      return { skillName: name, name, expertise: Number(exp) || 0 };
    }).filter((r) => r.skillName);
    return out.length ? out : '';
  };
  const mapEdu = (items) => {
    if (!Array.isArray(items)) return items || '';
    const out = items.map((row) => {
      const course = row.course || row.degree || '';
      let institute = row.institute || row.institution || '';
      let year = row.yearOfPassing || '';
      if (!year) year = [row.startYear, row.endYear].filter(Boolean).join(' - ');
      return { course, degree: course, institute, institution: institute, yearOfPassing: year, grade: row.grade || '' };
    }).filter((r) => r.course || r.institute);
    return out.length ? out : '';
  };
  const mapLang = (items) => {
    if (!Array.isArray(items)) return items || '';
    return items.map((row) => {
      if (typeof row === 'string') return { name: row, language: row };
      const n = row.language || row.name || '';
      const l = row.level || '';
      return { name: l ? n + ' (' + l + ')' : n, language: n, level: l };
    }).filter((r) => r.name);
  };
  const mapInt = (items) => {
    if (!Array.isArray(items)) return items || '';
    return items.map((row) => (typeof row === 'string' ? { hoby: row } : { hoby: row.hoby || row.hobby || row.name || '' })).filter((r) => r.hoby);
  };
  const mapProj = (items) => {
    if (!Array.isArray(items)) return items || '';
    return items.map((row) => ({
      projectTitle: row.projectTitle || row.title || '',
      title: row.title || row.projectTitle || '',
      roles: row.roles || row.role || '',
      role: row.role || row.roles || '',
      projectDescription: row.projectDescription || row.description || '',
      description: row.description || row.projectDescription || '',
    })).filter((r) => r.projectTitle);
  };
  const mapAch = (items) => {
    if (!Array.isArray(items)) return items || '';
    return items.map((row) => {
      const t = row.achivement || row.achievement || [row.title, row.description].filter(Boolean).join(' - ');
      return { achivement: t };
    }).filter((r) => r.achivement);
  };
  const mapExp = (items) => {
    if (!Array.isArray(items)) return items || '';
    return items.map((row) => ({
      jobTitle: row.jobTitle || row.designation || '',
      designation: row.designation || row.jobTitle || '',
      organization: row.organization || row.company || '',
      company: row.company || row.organization || '',
      from: row.from || row.startDate || '',
      to: row.to || row.endDate || '',
      startDate: row.startDate || row.from || '',
      endDate: row.endDate || row.to || '',
      description: row.description || '',
      location: row.location || '',
      present: (row.endDate || '').toString().toLowerCase() === 'present' ? true : '',
    }));
  };

  return {
    ...body,
    'Personal Details': personal,
    Skills: mapSkills(body.Skills),
    Education: mapEdu(body.Education),
    Experience: mapExp(body.Experience),
    Projects: mapProj(body.Projects),
    Language: mapLang(body.Language),
    Achivement: mapAch(body.Achivement || body.Achievement),
    Interests: mapInt(body.Interests),
  };
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { mapResumePayloadForTemplates };
}
